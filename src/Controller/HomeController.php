<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'message' => 'E-Commerce Backend API is running successfully!',
            'endpoints' => [
                'products' => '/api/products',
                'categories' => '/api/categories',
                'login' => '/api/login',
                'register' => '/api/register',
            ],
        ]);
    }
}
