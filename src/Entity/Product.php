<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Product name cannot be empty.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Product name cannot exceed {{ limit }} characters.'
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Product price cannot be empty.')]
    #[Assert\Type(type: 'numeric', message: 'Price must be a valid number.')]
    #[Assert\PositiveOrZero(message: 'Price must be zero or a positive value.')]
    private ?string $price = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull(message: 'Product stock cannot be null.')]
    #[Assert\Type(type: 'integer', message: 'Stock must be an integer.')]
    #[Assert\PositiveOrZero(message: 'Stock must be zero or a positive integer.')]
    private ?int $stock = 0;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => 'active'])]
    private string $status = 'active';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isNewArrival = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isBestSeller = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $tags = [];

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $weight = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $material = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $colour = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $size = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $jewelleryType = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Category $category = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isNewArrival(): bool
    {
        return $this->isNewArrival;
    }

    public function setIsNewArrival(bool $isNewArrival): static
    {
        $this->isNewArrival = $isNewArrival;

        return $this;
    }

    public function isBestSeller(): bool
    {
        return $this->isBestSeller;
    }

    public function setIsBestSeller(bool $isBestSeller): static
    {
        $this->isBestSeller = $isBestSeller;

        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getMaterial(): ?string
    {
        return $this->material;
    }

    public function setMaterial(?string $material): static
    {
        $this->material = $material;

        return $this;
    }

    public function getColour(): ?string
    {
        return $this->colour;
    }

    public function setColour(?string $colour): static
    {
        $this->colour = $colour;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getJewelleryType(): ?string
    {
        return $this->jewelleryType;
    }

    public function setJewelleryType(?string $jewelleryType): static
    {
        $this->jewelleryType = $jewelleryType;

        return $this;
    }
}
