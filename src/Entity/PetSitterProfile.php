<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\PetSitterProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PetSitterProfileRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_USER')"),
        new Put(security: "object.getUser() == user"),
        new Patch(security: "object.getUser() == user"),
    ],
    normalizationContext: ['groups' => ['sitter:read']],
    denormalizationContext: ['groups' => ['sitter:write']],
    order: ['rating' => 'DESC'],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'user.city' => 'exact',
    'user.city.region' => 'exact',
    'services' => 'exact',
    'acceptedAnimalTypes' => 'partial',
])]
#[ApiFilter(RangeFilter::class, properties: ['dailyRate', 'hourlyRate'])]
#[ApiFilter(BooleanFilter::class, properties: ['verified'])]
#[ApiFilter(OrderFilter::class, properties: ['dailyRate', 'rating', 'reviewCount', 'verified'])]
class PetSitterProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sitter:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'sitterProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sitter:read', 'sitter:write'])]
    private ?User $user = null;

    #[ORM\Column(length: 200)]
    #[Groups(['sitter:read', 'sitter:write', 'user:read'])]
    private string $headline = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['sitter:read', 'sitter:write'])]
    private ?string $description = null;

    /** Tarif horaire en MAD */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    #[Groups(['sitter:read', 'sitter:write', 'user:read'])]
    private ?string $hourlyRate = null;

    /** Tarif journalier en MAD */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    #[Groups(['sitter:read', 'sitter:write', 'user:read'])]
    private ?string $dailyRate = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['sitter:read', 'sitter:write'])]
    private ?int $experienceYears = null;

    /** Types d'animaux acceptés, ex: "dog,cat" */
    #[ORM\Column(length: 120, nullable: true)]
    #[Groups(['sitter:read', 'sitter:write'])]
    private ?string $acceptedAnimalTypes = null;

    /** @var Collection<int, Service> */
    #[ORM\ManyToMany(targetEntity: Service::class)]
    #[Groups(['sitter:read', 'sitter:write'])]
    private Collection $services;

    #[ORM\Column]
    #[Groups(['sitter:read'])]
    private bool $verified = false;

    /** Note moyenne (0-5), recalculée à chaque avis */
    #[ORM\Column(type: 'float')]
    #[Groups(['sitter:read', 'user:read'])]
    private float $rating = 0.0;

    #[ORM\Column]
    #[Groups(['sitter:read', 'user:read'])]
    private int $reviewCount = 0;

    /** Rayon d'intervention en km */
    #[ORM\Column(nullable: true)]
    #[Groups(['sitter:read', 'sitter:write'])]
    private ?int $serviceRadius = 10;

    /**
     * Pièce d'identité (n° CIN ou lien vers le document) — KYC.
     * Jamais sérialisée en lecture publique : seul l'admin y accède
     * via /api/admin/sitters/{id}/document.
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sitter:write'])]
    private ?string $idDocument = null;

    public function __construct()
    {
        $this->services = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getHeadline(): string
    {
        return $this->headline;
    }

    public function setHeadline(string $headline): static
    {
        $this->headline = $headline;
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

    public function getHourlyRate(): ?string
    {
        return $this->hourlyRate;
    }

    public function setHourlyRate(?string $hourlyRate): static
    {
        $this->hourlyRate = $hourlyRate;
        return $this;
    }

    public function getDailyRate(): ?string
    {
        return $this->dailyRate;
    }

    public function setDailyRate(?string $dailyRate): static
    {
        $this->dailyRate = $dailyRate;
        return $this;
    }

    public function getExperienceYears(): ?int
    {
        return $this->experienceYears;
    }

    public function setExperienceYears(?int $experienceYears): static
    {
        $this->experienceYears = $experienceYears;
        return $this;
    }

    public function getAcceptedAnimalTypes(): ?string
    {
        return $this->acceptedAnimalTypes;
    }

    public function setAcceptedAnimalTypes(?string $acceptedAnimalTypes): static
    {
        $this->acceptedAnimalTypes = $acceptedAnimalTypes;
        return $this;
    }

    /** @return Collection<int, Service> */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
        }
        return $this;
    }

    public function removeService(Service $service): static
    {
        $this->services->removeElement($service);
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;
        return $this;
    }

    public function getRating(): float
    {
        return $this->rating;
    }

    public function setRating(float $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getReviewCount(): int
    {
        return $this->reviewCount;
    }

    public function setReviewCount(int $reviewCount): static
    {
        $this->reviewCount = $reviewCount;
        return $this;
    }

    public function getServiceRadius(): ?int
    {
        return $this->serviceRadius;
    }

    public function setServiceRadius(?int $serviceRadius): static
    {
        $this->serviceRadius = $serviceRadius;
        return $this;
    }

    public function getIdDocument(): ?string
    {
        return $this->idDocument;
    }

    public function setIdDocument(?string $idDocument): static
    {
        $this->idDocument = $idDocument;
        return $this;
    }

    /** Indique si le gardien a soumis sa pièce d'identité (sans l'exposer). */
    #[Groups(['sitter:read'])]
    public function isIdDocumentSubmitted(): bool
    {
        return $this->idDocument !== null && $this->idDocument !== '';
    }
}
