<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->validator = $validator;
    }

    #[Route('', name: 'api_products_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $statusFilter = $request->query->get('status');
        $criteria = [];
        if ($statusFilter) {
            $criteria['status'] = $statusFilter;
        }

        $products = $this->productRepository->findBy($criteria, ['createdAt' => 'DESC']);
        
        $data = [];
        foreach ($products as $product) {
            $data[] = $this->formatProduct($product);
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_products_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json([
                'message' => 'Invalid UUID format.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json([
                'message' => 'Product not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->formatProduct($product), Response::HTTP_OK);
    }

    #[Route('', name: 'api_products_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json([
                'message' => 'Invalid JSON body.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product->setName($data['name'] ?? '');
        $product->setDescription($data['description'] ?? null);
        $product->setImage($data['image'] ?? null);
        
        // Ensure price is treated as a string to preserve decimal precision
        $price = isset($data['price']) ? (string) $data['price'] : '';
        $product->setPrice($price);
        
        $product->setStock($data['stock'] ?? 0);
        if (!empty($data['status'])) {
            $product->setStatus($data['status']);
        }
        $product->setIsNewArrival(!empty($data['is_new_arrival']) || !empty($data['isNewArrival']));
        $product->setIsBestSeller(!empty($data['is_best_seller']) || !empty($data['isBestSeller']));
        if (isset($data['tags']) && is_array($data['tags'])) {
            $product->setTags($data['tags']);
        }
        $product->setWeight($data['weight'] ?? null);
        $product->setMaterial($data['material'] ?? null);
        $product->setColour($data['colour'] ?? $data['color'] ?? null);
        $product->setSize($data['size'] ?? null);
        $product->setJewelleryType($data['jewellery_type'] ?? $data['jewelleryType'] ?? null);

        // Associate with category if provided
        if (!empty($data['category_id'])) {
            if (!Uuid::isValid($data['category_id'])) {
                return $this->json([
                    'message' => 'Validation failed.',
                    'errors' => ['category_id' => 'Invalid category UUID format.']
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $category = $this->categoryRepository->find($data['category_id']);
            if (!$category) {
                return $this->json([
                    'message' => 'Validation failed.',
                    'errors' => ['category_id' => 'Category not found.']
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $product->setCategory($category);
        }

        // Validate the product entity
        $errors = $this->validator->validate($product);
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

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($this->formatProduct($product), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_products_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $id, Request $request): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json([
                'message' => 'Invalid UUID format.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json([
                'message' => 'Product not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json([
                'message' => 'Invalid JSON body.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('name', $data)) {
            $product->setName($data['name'] ?? '');
        }
        if (array_key_exists('description', $data)) {
            $product->setDescription($data['description'] ?? null);
        }
        if (array_key_exists('image', $data)) {
            $product->setImage($data['image'] ?? null);
        }
        if (array_key_exists('price', $data)) {
            $product->setPrice(isset($data['price']) ? (string) $data['price'] : '');
        }
        if (array_key_exists('stock', $data)) {
            $product->setStock($data['stock'] ?? 0);
        }
        if (array_key_exists('status', $data)) {
            $product->setStatus($data['status'] ?? 'active');
        }
        if (array_key_exists('is_new_arrival', $data) || array_key_exists('isNewArrival', $data)) {
            $product->setIsNewArrival((bool)($data['is_new_arrival'] ?? $data['isNewArrival'] ?? false));
        }
        if (array_key_exists('is_best_seller', $data) || array_key_exists('isBestSeller', $data)) {
            $product->setIsBestSeller((bool)($data['is_best_seller'] ?? $data['isBestSeller'] ?? false));
        }
        if (array_key_exists('tags', $data) && is_array($data['tags'])) {
            $product->setTags($data['tags']);
        }
        if (array_key_exists('weight', $data)) {
            $product->setWeight($data['weight']);
        }
        if (array_key_exists('material', $data)) {
            $product->setMaterial($data['material']);
        }
        if (array_key_exists('colour', $data) || array_key_exists('color', $data)) {
            $product->setColour($data['colour'] ?? $data['color']);
        }
        if (array_key_exists('size', $data)) {
            $product->setSize($data['size']);
        }
        if (array_key_exists('jewellery_type', $data) || array_key_exists('jewelleryType', $data)) {
            $product->setJewelleryType($data['jewellery_type'] ?? $data['jewelleryType']);
        }
        if (array_key_exists('category_id', $data)) {
            $categoryId = $data['category_id'];
            if (empty($categoryId)) {
                $product->setCategory(null);
            } else {
                if (!Uuid::isValid($categoryId)) {
                    return $this->json([
                        'message' => 'Validation failed.',
                        'errors' => ['category_id' => 'Invalid category UUID format.']
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $category = $this->categoryRepository->find($categoryId);
                if (!$category) {
                    return $this->json([
                        'message' => 'Validation failed.',
                        'errors' => ['category_id' => 'Category not found.']
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $product->setCategory($category);
            }
        }

        // Validate the updated entity
        $errors = $this->validator->validate($product);
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

        return $this->json($this->formatProduct($product), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_products_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json([
                'message' => 'Invalid UUID format.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json([
                'message' => 'Product not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Product deleted successfully.'
        ], Response::HTTP_OK);
    }

    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'stock' => $product->getStock(),
            'status' => $product->getStatus(),
            'isNewArrival' => $product->isNewArrival(),
            'isBestSeller' => $product->isBestSeller(),
            'tags' => $product->getTags() ?? [],
            'weight' => $product->getWeight(),
            'material' => $product->getMaterial(),
            'colour' => $product->getColour(),
            'size' => $product->getSize(),
            'jewelleryType' => $product->getJewelleryType(),
            'image' => $product->getImage(),
            'category' => $product->getCategory() ? [
                'id' => $product->getCategory()->getId(),
                'name' => $product->getCategory()->getName(),
                'slug' => $product->getCategory()->getSlug()
            ] : null,
            'createdAt' => $product->getCreatedAt()?->format(\DateTimeInterface::ATOM)
        ];
    }
}
