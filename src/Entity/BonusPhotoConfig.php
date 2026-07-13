<?php

namespace App\Entity;

use App\Enum\BonusChallengeEnum;
use App\Repository\BonusPhotoConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: BonusPhotoConfigRepository::class)]
#[UniqueEntity(fields: ['cityEdition', 'challenge'])]
class BonusPhotoConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bonusPhotoConfigs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CityEdition $cityEdition = null;

    #[ORM\Column(enumType: BonusChallengeEnum::class)]
    private BonusChallengeEnum $challenge;

    #[ORM\Column]
    private float $points;

    public function __construct(BonusChallengeEnum $challenge, float $points)
    {
        $this->challenge = $challenge;
        $this->points = $points;
    }

    public function getId(): ?int { return $this->id; }

    public function getCityEdition(): ?CityEdition { return $this->cityEdition; }
    public function setCityEdition(?CityEdition $cityEdition): static { $this->cityEdition = $cityEdition; return $this; }

    public function getChallenge(): BonusChallengeEnum { return $this->challenge; }
    public function setChallenge(BonusChallengeEnum $challenge): static { $this->challenge = $challenge; return $this; }

    public function getPoints(): float { return $this->points; }
    public function setPoints(float $points): static { $this->points = $points; return $this; }
}
