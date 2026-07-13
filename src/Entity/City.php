<?php

namespace App\Entity;

use App\Enum\SocialLinkTypeEnum;
use App\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CityRepository::class)]
#[UniqueEntity('slug')]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/')]
    private string $slug = '';

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $organizationName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $organizationDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $organizationLogo = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $contactEmail = null;

    #[ORM\Column(nullable: true, enumType: SocialLinkTypeEnum::class)]
    private ?SocialLinkTypeEnum $socialLink1Type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $socialLink1Url = null;

    #[ORM\Column(nullable: true, enumType: SocialLinkTypeEnum::class)]
    private ?SocialLinkTypeEnum $socialLink2Type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $socialLink2Url = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/')]
    private ?string $primaryColor = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/')]
    private ?string $secondaryColor = null;

    /** @var Collection<int, CityEdition> */
    #[ORM\OneToMany(targetEntity: CityEdition::class, mappedBy: 'city', cascade: ['persist', 'remove'])]
    private Collection $cityEditions;

    /** @var Collection<int, Partner> */
    #[ORM\OneToMany(targetEntity: Partner::class, mappedBy: 'city', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $partners;

    /** @var Collection<int, Team> */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'city', cascade: ['persist', 'remove'])]
    private Collection $teams;

    /** @var Collection<int, User> */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'city')]
    private Collection $users;

    public function __construct()
    {
        $this->cityEditions = new ArrayCollection();
        $this->partners = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getOrganizationName(): ?string { return $this->organizationName; }
    public function setOrganizationName(?string $organizationName): static { $this->organizationName = $organizationName; return $this; }

    public function getOrganizationDescription(): ?string { return $this->organizationDescription; }
    public function setOrganizationDescription(?string $organizationDescription): static { $this->organizationDescription = $organizationDescription; return $this; }

    public function getOrganizationLogo(): ?string { return $this->organizationLogo; }
    public function setOrganizationLogo(?string $organizationLogo): static { $this->organizationLogo = $organizationLogo; return $this; }

    public function getContactEmail(): ?string { return $this->contactEmail; }
    public function setContactEmail(?string $contactEmail): static { $this->contactEmail = $contactEmail; return $this; }

    public function getSocialLink1Type(): ?SocialLinkTypeEnum { return $this->socialLink1Type; }
    public function setSocialLink1Type(?SocialLinkTypeEnum $socialLink1Type): static { $this->socialLink1Type = $socialLink1Type; return $this; }

    public function getSocialLink1Url(): ?string { return $this->socialLink1Url; }
    public function setSocialLink1Url(?string $socialLink1Url): static { $this->socialLink1Url = $socialLink1Url; return $this; }

    public function getSocialLink2Type(): ?SocialLinkTypeEnum { return $this->socialLink2Type; }
    public function setSocialLink2Type(?SocialLinkTypeEnum $socialLink2Type): static { $this->socialLink2Type = $socialLink2Type; return $this; }

    public function getSocialLink2Url(): ?string { return $this->socialLink2Url; }
    public function setSocialLink2Url(?string $socialLink2Url): static { $this->socialLink2Url = $socialLink2Url; return $this; }

    public function getPrimaryColor(): ?string { return $this->primaryColor; }
    public function setPrimaryColor(?string $primaryColor): static { $this->primaryColor = $primaryColor; return $this; }

    public function getSecondaryColor(): ?string { return $this->secondaryColor; }
    public function setSecondaryColor(?string $secondaryColor): static { $this->secondaryColor = $secondaryColor; return $this; }

    /** @return Collection<int, CityEdition> */
    public function getCityEditions(): Collection { return $this->cityEditions; }

    /** @return Collection<int, Partner> */
    public function getPartners(): Collection { return $this->partners; }

    /** @return Collection<int, Team> */
    public function getTeams(): Collection { return $this->teams; }

    /** @return Collection<int, User> */
    public function getUsers(): Collection { return $this->users; }

    public function __toString(): string { return $this->name; }

    public function getDisplayName(): string
    {
        $cityEdition = $this->getCurrentOrNextCityEdition();

        if ($cityEdition === null) {
            return $this->name;
        }

        $edition = $cityEdition->getEdition();

        $startDateFormatter = new \IntlDateFormatter('fr', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'd MMMM');
        $endDateFormatter = new \IntlDateFormatter('fr', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'd MMMM y');

        return sprintf(
            '%s - du %s au %s',
            $this->name,
            $startDateFormatter->format($edition->getStartDate()),
            $endDateFormatter->format($edition->getEndDate()),
        );
    }

    private function getCurrentOrNextCityEdition(): ?CityEdition
    {
        $now = new \DateTime();

        $upcoming = $this->cityEditions
            ->filter(static fn (CityEdition $cityEdition): bool => $cityEdition->getEdition()->getEndDate() >= $now)
            ->toArray();

        usort(
            $upcoming,
            static fn (CityEdition $a, CityEdition $b): int => $a->getEdition()->getStartDate() <=> $b->getEdition()->getStartDate(),
        );

        return $upcoming[0] ?? null;
    }
}
