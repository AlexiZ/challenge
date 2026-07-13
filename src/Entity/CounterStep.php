<?php

namespace App\Entity;

use App\Repository\CounterStepRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CounterStepRepository::class)]
class CounterStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column]
    #[Assert\Positive]
    private int $distanceKm = 0;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(inversedBy: 'counterSteps')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CityEdition $cityEdition = null;

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDistanceKm(): int { return $this->distanceKm; }
    public function setDistanceKm(int $distanceKm): static { $this->distanceKm = $distanceKm; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function getCityEdition(): ?CityEdition { return $this->cityEdition; }
    public function setCityEdition(?CityEdition $cityEdition): static { $this->cityEdition = $cityEdition; return $this; }

    public function __toString(): string { return $this->name . ' (' . $this->distanceKm . ' km)'; }
}
