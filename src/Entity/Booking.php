<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\Repository\BookingRepository;
use App\State\BookingProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER') and (object.getOwner() == user or object.getSitter() == user)"),
        new Post(security: "is_granted('ROLE_USER')", processor: BookingProcessor::class),
        new Patch(security: "is_granted('ROLE_USER') and (object.getOwner() == user or object.getSitter() == user)", processor: BookingProcessor::class),
    ],
    normalizationContext: ['groups' => ['booking:read']],
    denormalizationContext: ['groups' => ['booking:write']],
    order: ['createdAt' => 'DESC'],
)]
class Booking
{
    public const STATUS_PENDING = 'pending';     // demande envoyée
    public const STATUS_ACCEPTED = 'accepted';   // gardien a accepté
    public const STATUS_PAID = 'paid';           // payé via CMI
    public const STATUS_COMPLETED = 'completed'; // prestation terminée
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?User $sitter = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Animal::class)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?Animal $animal = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    #[Groups(['booking:read', 'booking:write'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    #[Groups(['booking:read', 'booking:write'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 20)]
    #[Groups(['booking:read', 'booking:write'])]
    private string $status = self::STATUS_PENDING;

    /** Montant total payé par le propriétaire, en MAD */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['booking:read'])]
    private string $totalPrice = '0.00';

    /** Commission DariPets prélevée sur le total, en MAD */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['booking:read'])]
    private string $commissionAmount = '0.00';

    /** Montant net reversé au gardien (total - commission), en MAD */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['booking:read'])]
    private string $sitterPayout = '0.00';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?string $address = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?string $note = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
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

    public function getSitter(): ?User
    {
        return $this->sitter;
    }

    public function setSitter(?User $sitter): static
    {
        $this->sitter = $sitter;
        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getAnimal(): ?Animal
    {
        return $this->animal;
    }

    public function setAnimal(?Animal $animal): static
    {
        $this->animal = $animal;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
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

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getCommissionAmount(): string
    {
        return $this->commissionAmount;
    }

    public function setCommissionAmount(string $commissionAmount): static
    {
        $this->commissionAmount = $commissionAmount;
        return $this;
    }

    public function getSitterPayout(): string
    {
        return $this->sitterPayout;
    }

    public function setSitterPayout(string $sitterPayout): static
    {
        $this->sitterPayout = $sitterPayout;
        return $this;
    }

    /** Calcule et fige commission + net gardien à partir du total. */
    public function applyCommission(float $rate): static
    {
        $total = (float) $this->totalPrice;
        $commission = round($total * $rate, 2);
        $this->commissionAmount = number_format($commission, 2, '.', '');
        $this->sitterPayout = number_format($total - $commission, 2, '.', '');
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    #[Groups(['booking:read'])]
    public function getNights(): int
    {
        if (!$this->startDate || !$this->endDate) {
            return 0;
        }
        return max(1, $this->startDate->diff($this->endDate)->days);
    }
}
