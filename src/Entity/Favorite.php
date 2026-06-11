<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\FavoriteRepository;
use App\State\FavoriteProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Gardien mis en favori par un propriétaire.
 */
#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
#[ORM\UniqueConstraint(columns: ['owner_id', 'profile_id'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER')", processor: FavoriteProcessor::class),
        new Delete(security: "is_granted('ROLE_USER') and object.getOwner() == user"),
    ],
    normalizationContext: ['groups' => ['favorite:read', 'sitter:read']],
    denormalizationContext: ['groups' => ['favorite:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['owner' => 'exact'])]
class Favorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['favorite:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: PetSitterProfile::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['favorite:read', 'favorite:write'])]
    private ?PetSitterProfile $profile = null;

    #[ORM\Column]
    #[Groups(['favorite:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getProfile(): ?PetSitterProfile
    {
        return $this->profile;
    }

    public function setProfile(?PetSitterProfile $profile): static
    {
        $this->profile = $profile;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
