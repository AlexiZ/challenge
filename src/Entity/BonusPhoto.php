<?php

namespace App\Entity;

use App\Enum\BonusChallengeEnum;
use App\Enum\BonusPhotoStatusEnum;
use App\Repository\BonusPhotoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonusPhotoRepository::class)]
class BonusPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bonusPhotos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'bonusPhotos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CityEdition $cityEdition = null;

    #[ORM\Column(enumType: BonusChallengeEnum::class)]
    private BonusChallengeEnum $challenge;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private bool $consentToPublish = false;

    #[ORM\Column(enumType: BonusPhotoStatusEnum::class)]
    private BonusPhotoStatusEnum $status = BonusPhotoStatusEnum::Pending;

    #[ORM\Column(nullable: true)]
    private ?float $pointsAwarded = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $submittedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $reviewedAt = null;

    #[ORM\ManyToOne]
    private ?User $reviewedBy = null;

    public function __construct()
    {
        $this->submittedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCityEdition(): ?CityEdition { return $this->cityEdition; }
    public function setCityEdition(?CityEdition $cityEdition): static { $this->cityEdition = $cityEdition; return $this; }

    public function getChallenge(): BonusChallengeEnum { return $this->challenge; }
    public function setChallenge(BonusChallengeEnum $challenge): static { $this->challenge = $challenge; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): static { $this->photo = $photo; return $this; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): static { $this->comment = $comment; return $this; }

    public function isConsentToPublish(): bool { return $this->consentToPublish; }
    public function setConsentToPublish(bool $consentToPublish): static { $this->consentToPublish = $consentToPublish; return $this; }

    public function getStatus(): BonusPhotoStatusEnum { return $this->status; }
    public function setStatus(BonusPhotoStatusEnum $status): static { $this->status = $status; return $this; }

    public function getPointsAwarded(): ?float { return $this->pointsAwarded; }
    public function setPointsAwarded(?float $pointsAwarded): static { $this->pointsAwarded = $pointsAwarded; return $this; }

    public function getSubmittedAt(): \DateTimeInterface { return $this->submittedAt; }

    public function getReviewedAt(): ?\DateTimeInterface { return $this->reviewedAt; }
    public function setReviewedAt(?\DateTimeInterface $reviewedAt): static { $this->reviewedAt = $reviewedAt; return $this; }

    public function getReviewedBy(): ?User { return $this->reviewedBy; }
    public function setReviewedBy(?User $reviewedBy): static { $this->reviewedBy = $reviewedBy; return $this; }
}
