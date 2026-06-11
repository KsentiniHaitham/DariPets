<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\UserRepository;
use App\State\UserPasswordHasher;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(
            uriTemplate: '/register',
            normalizationContext: ['groups' => ['user:read']],
            denormalizationContext: ['groups' => ['user:write']],
            validationContext: ['groups' => ['Default', 'registration']],
            processor: UserPasswordHasher::class,
        ),
        new Put(security: "is_granted('ROLE_USER') and object == user", processor: UserPasswordHasher::class),
        new Patch(security: "is_granted('ROLE_USER') and object == user", processor: UserPasswordHasher::class),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['email' => 'exact', 'type' => 'exact', 'city' => 'exact'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const TYPE_OWNER = 'owner';
    public const TYPE_SITTER = 'sitter';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'sitter:read', 'booking:read', 'message:read', 'review:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:write'])]
    private string $email;

    /** @var list<string> */
    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\Length(min: 6)]
    #[Groups(['user:write'])]
    private ?string $plainPassword = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:write', 'sitter:read', 'booking:read', 'message:read', 'review:read'])]
    private string $firstName;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:write', 'sitter:read', 'booking:read', 'review:read'])]
    private string $lastName;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $phone = null;

    /** owner | sitter */
    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_OWNER, self::TYPE_SITTER])]
    #[Groups(['user:read', 'user:write', 'sitter:read'])]
    private string $type = self::TYPE_OWNER;

    #[ORM\ManyToOne(targetEntity: City::class)]
    #[Groups(['user:read', 'user:write', 'sitter:read'])]
    private ?City $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write', 'sitter:read', 'message:read', 'review:read'])]
    private ?string $avatar = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write', 'sitter:read'])]
    private ?string $bio = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: PetSitterProfile::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?PetSitterProfile $sitterProfile = null;

    /** @var Collection<int, Animal> */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Animal::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $animals;

    public function __construct()
    {
        $this->animals = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
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

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
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

    public function getSitterProfile(): ?PetSitterProfile
    {
        return $this->sitterProfile;
    }

    public function setSitterProfile(?PetSitterProfile $sitterProfile): static
    {
        $this->sitterProfile = $sitterProfile;
        if ($sitterProfile !== null && $sitterProfile->getUser() !== $this) {
            $sitterProfile->setUser($this);
        }
        return $this;
    }

    /** @return Collection<int, Animal> */
    public function getAnimals(): Collection
    {
        return $this->animals;
    }

    public function addAnimal(Animal $animal): static
    {
        if (!$this->animals->contains($animal)) {
            $this->animals->add($animal);
            $animal->setOwner($this);
        }
        return $this;
    }

    public function removeAnimal(Animal $animal): static
    {
        if ($this->animals->removeElement($animal) && $animal->getOwner() === $this) {
            $animal->setOwner(null);
        }
        return $this;
    }

    #[Groups(['user:read', 'sitter:read', 'message:read', 'review:read'])]
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}
