<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use App\Repository\RegionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RegionRepository::class)]
#[ApiResource(
    operations: [new GetCollection(), new Get()],
    normalizationContext: ['groups' => ['region:read']],
)]
class Region
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['region:read', 'city:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Groups(['region:read', 'city:read'])]
    private string $name;

    #[ORM\Column(length: 120, nullable: true)]
    #[Groups(['region:read', 'city:read'])]
    private ?string $nameAr = null;

    /** @var Collection<int, City> */
    #[ORM\OneToMany(mappedBy: 'region', targetEntity: City::class)]
    #[Groups(['region:read'])]
    private Collection $cities;

    public function __construct()
    {
        $this->cities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /** @return Collection<int, City> */
    public function getCities(): Collection
    {
        return $this->cities;
    }
}
