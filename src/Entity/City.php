<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\CityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CityRepository::class)]
#[ApiResource(
    operations: [new GetCollection(), new Get()],
    normalizationContext: ['groups' => ['city:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'region' => 'exact'])]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['city:read', 'user:read', 'sitter:read', 'region:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Groups(['city:read', 'user:read', 'sitter:read', 'region:read'])]
    private string $name;

    #[ORM\Column(length: 120, nullable: true)]
    #[Groups(['city:read', 'user:read', 'sitter:read', 'region:read'])]
    private ?string $nameAr = null;

    #[ORM\ManyToOne(targetEntity: Region::class, inversedBy: 'cities')]
    #[Groups(['city:read'])]
    private ?Region $region = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['city:read', 'sitter:read'])]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['city:read', 'sitter:read'])]
    private ?float $longitude = null;

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

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): static
    {
        $this->region = $region;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }
}
