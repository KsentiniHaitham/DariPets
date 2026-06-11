<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\AnimalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnimalRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_USER')"),
        new Put(security: "object.getOwner() == user"),
        new Delete(security: "object.getOwner() == user"),
    ],
    normalizationContext: ['groups' => ['animal:read', 'booking:read']],
    denormalizationContext: ['groups' => ['animal:write']],
)]
class Animal
{
    public const TYPE_DOG = 'dog';
    public const TYPE_CAT = 'cat';
    public const TYPE_BIRD = 'bird';
    public const TYPE_RODENT = 'rodent';
    public const TYPE_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['animal:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'animals')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['animal:read', 'animal:write'])]
    private ?User $owner = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['animal:read', 'animal:write', 'booking:read'])]
    private string $name;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_DOG, self::TYPE_CAT, self::TYPE_BIRD, self::TYPE_RODENT, self::TYPE_OTHER])]
    #[Groups(['animal:read', 'animal:write', 'booking:read'])]
    private string $type = self::TYPE_DOG;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['animal:read', 'animal:write'])]
    private ?string $breed = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['animal:read', 'animal:write'])]
    private ?int $age = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['animal:read', 'animal:write'])]
    private ?string $notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['animal:read', 'animal:write', 'booking:read'])]
    private ?string $photo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getBreed(): ?string
    {
        return $this->breed;
    }

    public function setBreed(?string $breed): static
    {
        $this->breed = $breed;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }
}
