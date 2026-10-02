<?php

namespace App\Controller;

use App\Entity\Category;
use App\Enum\CategoryStatus;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->categoryRepository = $categoryRepository;
        $this->validator = $validator;
    }

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $categories = $this->categoryRepository->findBy([], ['createdAt' => 'DESC']);
            
            $data = [];
            foreach ($categories as $category) {
                $data[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                    'description' => $category->getDescription(),
                    'image' => $category->getImage(),
                    'status' => $category->getStatus()->value,
                    'parent' => $category->getParent() ? [
                        'id' => $category->getParent()->getId(),
                        'name' => $category->getParent()->getName()
                    ] : null,
                    'createdAt' => $category->getCreatedAt()->format(\DateTimeInterface::ATOM)
                ];
            }

            return $this->json($data, Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_categories_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json([
                'message' => 'Invalid UUID format.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json([
                'message' => 'Category not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $children = [];
        foreach ($category->getChildren() as $child) {
            $children[] = [
                'id' => $child->getId(),
                'name' => $child->getName(),
                'slug' => $child->getSlug()
            ];
        }

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'description' => $category->getDescription(),
            'image' => $category->getImage(),
            'status' => $category->getStatus()->value,
            'parent' => $category->getParent() ? [
                'id' => $category->getParent()->getId(),
                'name' => $category->getParent()->getName()
            ] : null,
            'children' => $children,
            'createdAt' => $category->getCreatedAt()->format(\DateTimeInterface::ATOM)
        ], Response::HTTP_OK);
    }

    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json([
                'message' => 'Invalid JSON body.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setName($data['name'] ?? '');
        $category->setDescription($data['description'] ?? null);
        $category->setImage($data['image'] ?? null);

        // Convert string status to Backed Enum
        $statusStr = $data['status'] ?? 'active';
        $statusEnum = CategoryStatus::tryFrom($statusStr);
        if ($statusEnum === null) {
            return $this->json([
                'message' => 'Validation failed.',
                'errors' => ['status' => 'Status must be active or inactive.']
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $category->setStatus($statusEnum);

        // Set parent category if provided
        if (!empty($data['parent_id'])) {
            if (!Uuid::isValid($data['parent_id'])) {
                return $this->json([
                    'message' => 'Validation failed.',
                    'errors' => ['parent_id' => 'Invalid parent UUID format.']
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $parent = $this->categoryRepository->find($data['parent_id']);
            if (!$parent) {
                return $this->json([
                    'message' => 'Validation failed.',
                    'errors' => ['parent_id' => 'Parent category not found.']
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $category->setParent($parent);
        }

        // Validate the entity
        $errors = $this->validator->validate($category);
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

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'status' => $category->getStatus()->value,
            'parent' => $category->getParent() ? ['id' => $category->getParent()->getId()] : null
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_categories_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $id, Request $request): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json([
                'message' => 'Invalid UUID format.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json([
                'message' => 'Category not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json([
                'message' => 'Invalid JSON body.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('name', $data)) {
            $category->setName($data['name'] ?? '');
        }
        if (array_key_exists('description', $data)) {
            $category->setDescription($data['description'] ?? null);
        }
        if (array_key_exists('image', $data)) {
            $category->setImage($data['image'] ?? null);
        }

        if (array_key_exists('status', $data)) {
            $statusStr = $data['status'] ?? 'active';
            $statusEnum = CategoryStatus::tryFrom($statusStr);
            if ($statusEnum === null) {
                return $this->json([
                    'message' => 'Validation failed.',
                    'errors' => ['status' => 'Status must be active or inactive.']
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $category->setStatus($statusEnum);
        }

        if (array_key_exists('parent_id', $data)) {
            $parentId = $data['parent_id'];
            if (empty($parentId)) {
                $category->setParent(null);
            } else {
                if (!Uuid::isValid($parentId)) {
                    return $this->json([
                        'message' => 'Validation failed.',
                        'errors' => ['parent_id' => 'Invalid parent UUID format.']
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                
                // Prevent self-parent loop
                if ($parentId === $id) {
                    return $this->json([
                        'message' => 'Validation failed.',
                        'errors' => ['parent_id' => 'A category cannot be its own parent.']
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $parent = $this->categoryRepository->find($parentId);
                if (!$parent) {
                    return $this->json([
                        'message' => 'Validation failed.',
                        'errors' => ['parent_id' => 'Parent category not found.']
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $category->setParent($parent);
            }
        }

        // Validate the entity
        $errors = $this->validator->validate($category);
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

        $this->entityManager->flush();

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'status' => $category->getStatus()->value,
            'parent' => $category->getParent() ? ['id' => $category->getParent()->getId()] : null
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_categories_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json([
                'message' => 'Invalid UUID format.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json([
                'message' => 'Category not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Category deleted successfully.'
        ], Response::HTTP_OK);
    }
}
