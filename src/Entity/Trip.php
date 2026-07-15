<?php

namespace App\Entity;

use App\Enum\TripModeEnum;
use App\Repository\TripRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TripRepository::class)]
class Trip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trips')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'trips')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CityEdition $cityEdition = null;

    #[ORM\Column(enumType: TripModeEnum::class)]
    private TripModeEnum $mode = TripModeEnum::Bike;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(0.5, message: 'Vous ne pouvez pas saisir un trajet de moins de 0,5 km.')]
    private float $distanceKm = 0.0;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $tripDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column]
    private float $pointsGenerated = 0.0;

    public function __construct()
    {
        $this->tripDate = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCityEdition(): ?CityEdition { return $this->cityEdition; }
    public function setCityEdition(?CityEdition $cityEdition): static { $this->cityEdition = $cityEdition; return $this; }

    public function getMode(): TripModeEnum { return $this->mode; }
    public function setMode(TripModeEnum $mode): static { $this->mode = $mode; return $this; }

    public function getDistanceKm(): float { return $this->distanceKm; }
    public function setDistanceKm(float $distanceKm): static { $this->distanceKm = $distanceKm; return $this; }

    public function getTripDate(): \DateTimeInterface { return $this->tripDate; }
    public function setTripDate(\DateTimeInterface $tripDate): static { $this->tripDate = $tripDate; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getPointsGenerated(): float { return $this->pointsGenerated; }
    public function setPointsGenerated(float $pointsGenerated): static { $this->pointsGenerated = $pointsGenerated; return $this; }

    public function isSuspicious(): bool
    {
        if ($this->cityEdition === null) {
            return false;
        }

        $edition = $this->cityEdition->getEdition();
        if ($edition === null) {
            return false;
        }

        // Hors période
        $tripTs = $this->tripDate->getTimestamp();
        if ($tripTs < $edition->getStartDate()->getTimestamp() || $tripTs > $edition->getEndDate()->getTimestamp()) {
            return true;
        }

        // Distance suspecte
        return match($this->mode) {
            TripModeEnum::Bike => $this->distanceKm > $this->cityEdition->getSuspiciousDistanceBike(),
            TripModeEnum::Walk => $this->distanceKm > $this->cityEdition->getSuspiciousDistanceWalk(),
        };
    }

    public function getSuspiciousReason(): ?string
    {
        if (!$this->isSuspicious()) {
            return null;
        }

        $edition = $this->cityEdition?->getEdition();
        if ($edition === null) {
            return null;
        }

        $tripTs = $this->tripDate->getTimestamp();
        if ($tripTs < $edition->getStartDate()->getTimestamp() || $tripTs > $edition->getEndDate()->getTimestamp()) {
            return 'out_of_period';
        }

        return 'suspicious_distance';
    }
}
