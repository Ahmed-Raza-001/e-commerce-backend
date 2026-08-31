<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the activation of the security firewall.');
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json([
                'message' => 'Invalid JSON body.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? '';
        $plainPassword = $data['password'] ?? '';
        $roles = $data['roles'] ?? ['ROLE_USER'];

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);

        // Basic password strength check
        if (strlen($plainPassword) < 6) {
            return $this->json([
                'message' => 'Validation failed.',
                'errors' => ['password' => 'Password must be at least 6 characters long.']
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate entity constraints
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'message' => 'Validation failed.',
                'errors' => $errorMessages
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Hash the password and save
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Unauthorized.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ], Response::HTTP_OK);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse([
            'message' => 'Logged out successfully.'
        ], Response::HTTP_OK);

        // Clears the secure BEARER cookie by setting it to expire
        $response->headers->clearCookie('BEARER', '/');

        return $response;
    }

    #[Route('/api/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refreshToken(
        Request $request,
        UserRepository $userRepository,
        \Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $token = $data['token'] ?? null;

        if (!$token) {
            $authHeader = $request->headers->get('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            }
        }

        if (!$token) {
            return $this->json([
                'message' => 'Token not provided.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return $this->json([
                'message' => 'Invalid JWT token structure.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'));
        $payload = json_decode($payloadJson, true);

        if (!$payload || (!isset($payload['username']) && !isset($payload['email']))) {
            return $this->json([
                'message' => 'Invalid JWT payload.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $payload['username'] ?? $payload['email'];
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json([
                'message' => 'User associated with token not found.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Issue a fresh JWT token for the user
        $newToken = $jwtManager->create($user);

        return $this->json([
            'token' => $newToken,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]
        ], Response::HTTP_OK);
    }
}

