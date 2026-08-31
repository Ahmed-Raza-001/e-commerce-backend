<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use App\Enum\CategoryStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-data',
    description: 'Seed initial product and category data into the database.',
)]
class SeedDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if categories already exist
        $existingCount = $this->entityManager->getRepository(Category::class)->count([]);
        if ($existingCount > 0) {
            $io->note('Database already contains category data. Skipping seed.');
            return Command::SUCCESS;
        }

        // Create Categories
        $categoriesData = [
            ['name' => 'Necklaces & Pendants', 'desc' => 'Handcrafted royal imitation necklaces and bridal sets', 'image' => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=600&q=80'],
            ['name' => 'Earrings & Jhumkas', 'desc' => 'Traditional Chandbalis, Jhumkas, and Kundan earrings', 'image' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=600&q=80'],
            ['name' => 'Bangles & Bracelets', 'desc' => 'Kada bangles, gold plated bracelets, and velvet set bangles', 'image' => 'https://images.unsplash.com/photo-1611591475140-1a733796d11f?auto=format&fit=crop&w=600&q=80'],
            ['name' => 'Rings & Solitaires', 'desc' => 'Statement cocktail rings and adjustable imitation rings', 'image' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=600&q=80'],
            ['name' => 'Bridal Sets', 'desc' => 'Full heavy heritage bridal jewelry sets', 'image' => 'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?auto=format&fit=crop&w=600&q=80']
        ];

        $categories = [];
        foreach ($categoriesData as $cData) {
            $cat = new Category();
            $cat->setName($cData['name']);
            $cat->setDescription($cData['desc']);
            $cat->setImage($cData['image']);
            $cat->setStatus(CategoryStatus::ACTIVE);
            $this->entityManager->persist($cat);
            $categories[$cData['name']] = $cat;
        }

        // Create Sample Products
        $productsData = [
            [
                'name' => 'Royal Kundan Choker Necklace Set',
                'desc' => 'Exquisite heritage Kundan choker set plated in 24k gold finish with matching earrings.',
                'price' => '3499.00',
                'stock' => 18,
                'image' => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=600&q=80',
                'category' => 'Necklaces & Pendants'
            ],
            [
                'name' => 'Heritage Pearl Jhumka Earrings',
                'desc' => 'Hand-crafted antique gold jhumka earrings featuring freshwater pearls and intricate carving.',
                'price' => '1299.00',
                'stock' => 42,
                'image' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=600&q=80',
                'category' => 'Earrings & Jhumkas'
            ],
            [
                'name' => 'Antique Temple Jewelry Kada Bangle',
                'desc' => 'Statement openable Temple design Kada with ruby synthetic stones.',
                'price' => '1899.00',
                'stock' => 15,
                'image' => 'https://images.unsplash.com/photo-1611591475140-1a733796d11f?auto=format&fit=crop&w=600&q=80',
                'category' => 'Bangles & Bracelets'
            ],
            [
                'name' => 'Emerald Green Cocktail Ring',
                'desc' => 'Adjustable gold-plated statement ring set with cubic zirconia crystals.',
                'price' => '899.00',
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=600&q=80',
                'category' => 'Rings & Solitaires'
            ],
            [
                'name' => 'Grand Maharani Bridal Jewelry Collection',
                'desc' => 'Complete 7-piece bridal set with Mathapatti, Nath, Choker, Long Haar, and Haathphool.',
                'price' => '12999.00',
                'stock' => 5,
                'image' => 'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?auto=format&fit=crop&w=600&q=80',
                'category' => 'Bridal Sets'
            ],
            [
                'name' => 'Zircon Solitaire Pendant Necklace',
                'desc' => 'Minimalist rose gold chain featuring a brilliant cut American Diamond solitaire.',
                'price' => '1499.00',
                'stock' => 25,
                'image' => 'https://images.unsplash.com/photo-1599643477877-530eb83abc8e?auto=format&fit=crop&w=600&q=80',
                'category' => 'Necklaces & Pendants'
            ]
        ];

        foreach ($productsData as $pData) {
            $product = new Product();
            $product->setName($pData['name']);
            $product->setDescription($pData['desc']);
            $product->setPrice($pData['price']);
            $product->setStock($pData['stock']);
            $product->setImage($pData['image']);
            if (isset($categories[$pData['category']])) {
                $product->setCategory($categories[$pData['category']]);
            }
            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();

        $io->success('Database seeded successfully with initial categories and products!');
        return Command::SUCCESS;
    }
}
