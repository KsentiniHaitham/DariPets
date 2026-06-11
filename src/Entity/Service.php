<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use App\Repository\ServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Type de prestation : garde à domicile, pension, promenade, visite, etc.
 */
#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ApiResource(
    operations: [new GetCollection(), new Get()],
    normalizationContext: ['groups' => ['service:read']],
)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['service:read', 'sitter:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 60, unique: true)]
    #[Groups(['service:read', 'sitter:read', 'booking:read'])]
    private string $code;

    #[ORM\Column(length: 150)]
    #[Groups(['service:read', 'sitter:read', 'booking:read'])]
    private string $name;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['service:read', 'sitter:read', 'booking:read'])]
    private ?string $nameAr = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['service:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 60, nullable: true)]
    #[Groups(['service:read', 'sitter:read'])]
    private ?string $icon = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getNameAr(): ?string
    {
        return $this->nameAr;
    }

    public function setNameAr(?string $nameAr): static
    {
        $this->nameAr = $nameAr;
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }
}
