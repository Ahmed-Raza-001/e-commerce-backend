<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(Connection $connection): JsonResponse
    {
        $dbStatus = 'unknown';
        $dbError = null;
        try {
            $connection->executeQuery('SELECT 1');
            $dbStatus = 'connected';
        } catch (\Throwable $e) {
            $dbStatus = 'failed';
            $dbError = $e->getMessage();
        }

        return $this->json([
            'status' => 'success',
            'message' => 'E-Commerce Backend API is running successfully!',
            'database' => [
                'status' => $dbStatus,
                'error' => $dbError,
            ],
            'endpoints' => [
                'products' => '/api/products',
                'categories' => '/api/categories',
                'login' => '/api/login',
                'register' => '/api/register',
            ],
        ]);
    }
}
