<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ReportRepository;
use App\State\ReportProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Signalement d'un utilisateur par un autre (comportement abusif, fraude…).
 * Créé par tout utilisateur connecté, traité par l'admin.
 */
#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_USER')", processor: ReportProcessor::class),
        new Patch(security: "is_granted('ROLE_ADMIN')", denormalizationContext: ['groups' => ['report:patch']]),
    ],
    normalizationContext: ['groups' => ['report:read']],
    denormalizationContext: ['groups' => ['report:write']],
    order: ['createdAt' => 'DESC'],
)]
class Report
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    public const REASONS = ['contournement', 'comportement', 'fraude', 'spam', 'autre'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['report:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['report:read'])]
    private ?User $reporter = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['report:read', 'report:write'])]
    private ?User $reported = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: self::REASONS)]
    #[Groups(['report:read', 'report:write'])]
    private string $reason = 'autre';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['report:read', 'report:write'])]
    private ?string $details = null;

    /** pending | resolved | dismissed — modifiable uniquement par l'admin (Patch) */
    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_RESOLVED, self::STATUS_DISMISSED])]
    #[Groups(['report:read', 'report:patch'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    #[Groups(['report:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $reporter): static
    {
        $this->reporter = $reporter;
        return $this;
    }

    public function getReported(): ?User
    {
        return $this->reported;
    }

    public function setReported(?User $reported): static
    {
        $this->reported = $reported;
        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
