<?php

namespace App\Entity;

use App\Repository\CityEditionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CityEditionRepository::class)]
#[UniqueEntity(fields: ['city', 'edition'], message: 'Cette ville participe déjà à cette édition.')]
class CityEdition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cityEditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    #[ORM\ManyToOne(inversedBy: 'cityEditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Edition $edition = null;

    #[ORM\Column]
    private int $targetParticipants = 50;

    #[ORM\Column]
    private int $targetDistanceKm = 5000;

    #[ORM\Column]
    private float $pointsPerDay = 1.0;

    #[ORM\Column]
    private float $pointsPerKmBike = 0.1;

    #[ORM\Column]
    private float $pointsPerKmWalk = 0.2;

    #[ORM\Column]
    private float $suspiciousDistanceBike = 200.0;

    #[ORM\Column]
    private float $suspiciousDistanceWalk = 50.0;

    #[ORM\Column]
    private bool $registrationsOpen = true;

    #[ORM\Column]
    private bool $tripsEntryOpen = true;

    #[ORM\Column]
    private bool $photoChallengesEnabled = true;

    #[ORM\Column]
    private bool $rankingsPublic = true;

    #[ORM\Column]
    private bool $profilesPublic = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $organizerMessage = null;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'city_edition_participants')]
    private Collection $participants;

    /** @var Collection<int, Trip> */
    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'cityEdition', cascade: ['remove'])]
    private Collection $trips;

    /** @var Collection<int, BonusPhoto> */
    #[ORM\OneToMany(targetEntity: BonusPhoto::class, mappedBy: 'cityEdition', cascade: ['remove'])]
    private Collection $bonusPhotos;

    /** @var Collection<int, CounterStep> */
    #[ORM\OneToMany(targetEntity: CounterStep::class, mappedBy: 'cityEdition', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $counterSteps;

    /** @var Collection<int, BonusPhotoConfig> */
    #[ORM\OneToMany(targetEntity: BonusPhotoConfig::class, mappedBy: 'cityEdition', cascade: ['persist', 'remove'])]
    private Collection $bonusPhotoConfigs;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->trips = new ArrayCollection();
        $this->bonusPhotos = new ArrayCollection();
        $this->counterSteps = new ArrayCollection();
        $this->bonusPhotoConfigs = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCity(): ?City { return $this->city; }
    public function setCity(?City $city): static { $this->city = $city; return $this; }

    public function getEdition(): ?Edition { return $this->edition; }
    public function setEdition(?Edition $edition): static { $this->edition = $edition; return $this; }

    public function getTargetParticipants(): int { return $this->targetParticipants; }
    public function setTargetParticipants(int $targetParticipants): static { $this->targetParticipants = $targetParticipants; return $this; }

    public function getTargetDistanceKm(): int { return $this->targetDistanceKm; }
    public function setTargetDistanceKm(int $targetDistanceKm): static { $this->targetDistanceKm = $targetDistanceKm; return $this; }

    public function getPointsPerDay(): float { return $this->pointsPerDay; }
    public function setPointsPerDay(float $pointsPerDay): static { $this->pointsPerDay = $pointsPerDay; return $this; }

    public function getPointsPerKmBike(): float { return $this->pointsPerKmBike; }
    public function setPointsPerKmBike(float $pointsPerKmBike): static { $this->pointsPerKmBike = $pointsPerKmBike; return $this; }

    public function getPointsPerKmWalk(): float { return $this->pointsPerKmWalk; }
    public function setPointsPerKmWalk(float $pointsPerKmWalk): static { $this->pointsPerKmWalk = $pointsPerKmWalk; return $this; }

    public function getSuspiciousDistanceBike(): float { return $this->suspiciousDistanceBike; }
    public function setSuspiciousDistanceBike(float $v): static { $this->suspiciousDistanceBike = $v; return $this; }

    public function getSuspiciousDistanceWalk(): float { return $this->suspiciousDistanceWalk; }
    public function setSuspiciousDistanceWalk(float $v): static { $this->suspiciousDistanceWalk = $v; return $this; }

    public function isRegistrationsOpen(): bool { return $this->registrationsOpen; }
    public function setRegistrationsOpen(bool $v): static { $this->registrationsOpen = $v; return $this; }

    public function isTripsEntryOpen(): bool { return $this->tripsEntryOpen; }
    public function setTripsEntryOpen(bool $v): static { $this->tripsEntryOpen = $v; return $this; }

    public function isPhotoChallengesEnabled(): bool { return $this->photoChallengesEnabled; }
    public function setPhotoChallengesEnabled(bool $v): static { $this->photoChallengesEnabled = $v; return $this; }

    public function isRankingsPublic(): bool { return $this->rankingsPublic; }
    public function setRankingsPublic(bool $v): static { $this->rankingsPublic = $v; return $this; }

    public function isProfilesPublic(): bool { return $this->profilesPublic; }
    public function setProfilesPublic(bool $v): static { $this->profilesPublic = $v; return $this; }

    public function getOrganizerMessage(): ?string { return $this->organizerMessage; }
    public function setOrganizerMessage(?string $organizerMessage): static { $this->organizerMessage = $organizerMessage; return $this; }

    /** @return Collection<int, User> */
    public function getParticipants(): Collection { return $this->participants; }

    public function addParticipant(User $user): static
    {
        if (!$this->participants->contains($user)) {
            $this->participants->add($user);
        }
        return $this;
    }

    public function removeParticipant(User $user): static
    {
        $this->participants->removeElement($user);
        return $this;
    }

    /** @return Collection<int, Trip> */
    public function getTrips(): Collection { return $this->trips; }

    /** @return Collection<int, BonusPhoto> */
    public function getBonusPhotos(): Collection { return $this->bonusPhotos; }

    /** @return Collection<int, CounterStep> */
    public function getCounterSteps(): Collection { return $this->counterSteps; }

    /** @return Collection<int, BonusPhotoConfig> */
    public function getBonusPhotoConfigs(): Collection { return $this->bonusPhotoConfigs; }

    public function isActive(): bool
    {
        return $this->edition?->isActive() ?? false;
    }

    public function __toString(): string
    {
        return ($this->city?->getName() ?? '?') . ' ' . ($this->edition?->getName() ?? '?');
    }
}
