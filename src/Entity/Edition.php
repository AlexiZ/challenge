<?php

namespace App\Entity;

use App\Repository\EditionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EditionRepository::class)]
class Edition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column]
    private int $year;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $endDate;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $warmupStartDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $warmupEndDate = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $bikeModeEnabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $walkModeEnabled = true;

    /** @var Collection<int, CityEdition> */
    #[ORM\OneToMany(targetEntity: CityEdition::class, mappedBy: 'edition', cascade: ['persist', 'remove'])]
    private Collection $cityEditions;

    public function __construct()
    {
        $this->cityEditions = new ArrayCollection();
        $this->year = (int) date('Y');
        $this->startDate = new \DateTime('first day of June this year');
        $this->endDate = new \DateTime('last day of June this year');
    }

    public function __toString(): string { return $this->name . ' (' . $this->year . ')'; }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getYear(): int { return $this->year; }
    public function setYear(int $year): static { $this->year = $year; return $this; }

    public function getStartDate(): \DateTimeInterface { return $this->startDate; }
    public function setStartDate(\DateTimeInterface $startDate): static { $this->startDate = $startDate; return $this; }

    public function getEndDate(): \DateTimeInterface { return $this->endDate; }
    public function setEndDate(\DateTimeInterface $endDate): static { $this->endDate = $endDate; return $this; }

    public function getWarmupStartDate(): ?\DateTimeInterface { return $this->warmupStartDate; }
    public function setWarmupStartDate(?\DateTimeInterface $warmupStartDate): static { $this->warmupStartDate = $warmupStartDate; return $this; }

    public function getWarmupEndDate(): ?\DateTimeInterface { return $this->warmupEndDate; }
    public function setWarmupEndDate(?\DateTimeInterface $warmupEndDate): static { $this->warmupEndDate = $warmupEndDate; return $this; }

    public function isBikeModeEnabled(): bool { return $this->bikeModeEnabled; }
    public function setBikeModeEnabled(bool $bikeModeEnabled): static { $this->bikeModeEnabled = $bikeModeEnabled; return $this; }

    public function isWalkModeEnabled(): bool { return $this->walkModeEnabled; }
    public function setWalkModeEnabled(bool $walkModeEnabled): static { $this->walkModeEnabled = $walkModeEnabled; return $this; }

    #[Assert\Callback]
    public function validateTransportModes(ExecutionContextInterface $context): void
    {
        if (!$this->bikeModeEnabled && !$this->walkModeEnabled) {
            $context->buildViolation('Impossible de désactiver les deux modes de transport.')
                ->atPath('walkModeEnabled')
                ->addViolation();
        }
    }

    /** @return Collection<int, CityEdition> */
    public function getCityEditions(): Collection { return $this->cityEditions; }

    public function isActive(): bool
    {
        $now = new \DateTime();
        return $now >= $this->startDate && $now <= $this->endDate;
    }

    public function getDurationDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days + 1;
    }
}
