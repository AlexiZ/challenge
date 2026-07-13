<?php

namespace App\Entity;

use App\Enum\BikeTypeEnum;
use App\Enum\CyclistProfileEnum;
use App\Enum\GenderEnum;
use App\Enum\MainBarrierEnum;
use App\Enum\PerceivedBenefitEnum;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email.')]
#[UniqueEntity(fields: ['username'], message: 'Ce pseudo est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $lastName = '';

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9._-]+$/', message: 'Le pseudo ne peut contenir que des lettres minuscules, chiffres, points, tirets et underscores.')]
    private string $username = '';

    #[ORM\Column(enumType: CyclistProfileEnum::class, nullable: true)]
    private ?CyclistProfileEnum $cyclistProfile = null;

    #[ORM\Column(enumType: BikeTypeEnum::class, nullable: true)]
    private ?BikeTypeEnum $bikeType = null;

    #[ORM\Column(enumType: PerceivedBenefitEnum::class, nullable: true)]
    private ?PerceivedBenefitEnum $perceivedBenefit = null;

    #[ORM\Column(enumType: MainBarrierEnum::class, nullable: true)]
    private ?MainBarrierEnum $mainBarrier = null;

    #[ORM\Column(enumType: GenderEnum::class, nullable: true)]
    private ?GenderEnum $gender = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 10, max: 100)]
    private ?int $age = null;

    #[ORM\Column]
    private bool $receiveEmails = true;

    #[ORM\Column]
    private bool $publicProfile = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 36, unique: true)]
    private string $personalToken = '';

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?City $city = null;

    /** @var Collection<int, Team> */
    #[ORM\ManyToMany(targetEntity: Team::class, mappedBy: 'members')]
    private Collection $teams;

    /** @var Collection<int, Trip> */
    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $trips;

    /** @var Collection<int, BonusPhoto> */
    #[ORM\OneToMany(targetEntity: BonusPhoto::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $bonusPhotos;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->trips = new ArrayCollection();
        $this->bonusPhotos = new ArrayCollection();
        $this->personalToken = $this->generateToken();
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(18));
    }

    public function regeneratePersonalToken(): void
    {
        $this->personalToken = $this->generateToken();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return $this->email; }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getFullName(): string { return $this->firstName . ' ' . $this->lastName; }

    public function getInitials(): string
    {
        return mb_strtoupper(mb_substr($this->firstName, 0, 1) . mb_substr($this->lastName, 0, 1));
    }

    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }

    public function getCyclistProfile(): ?CyclistProfileEnum { return $this->cyclistProfile; }
    public function setCyclistProfile(?CyclistProfileEnum $cyclistProfile): static { $this->cyclistProfile = $cyclistProfile; return $this; }

    public function getBikeType(): ?BikeTypeEnum { return $this->bikeType; }
    public function setBikeType(?BikeTypeEnum $bikeType): static { $this->bikeType = $bikeType; return $this; }

    public function getPerceivedBenefit(): ?PerceivedBenefitEnum { return $this->perceivedBenefit; }
    public function setPerceivedBenefit(?PerceivedBenefitEnum $perceivedBenefit): static { $this->perceivedBenefit = $perceivedBenefit; return $this; }

    public function getMainBarrier(): ?MainBarrierEnum { return $this->mainBarrier; }
    public function setMainBarrier(?MainBarrierEnum $mainBarrier): static { $this->mainBarrier = $mainBarrier; return $this; }

    public function getGender(): ?GenderEnum { return $this->gender; }
    public function setGender(?GenderEnum $gender): static { $this->gender = $gender; return $this; }

    public function getAge(): ?int { return $this->age; }
    public function setAge(?int $age): static { $this->age = $age; return $this; }

    public function isReceiveEmails(): bool { return $this->receiveEmails; }
    public function setReceiveEmails(bool $receiveEmails): static { $this->receiveEmails = $receiveEmails; return $this; }

    public function isPublicProfile(): bool { return $this->publicProfile; }
    public function setPublicProfile(bool $publicProfile): static { $this->publicProfile = $publicProfile; return $this; }

    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }

    public function getPersonalToken(): string { return $this->personalToken; }
    public function setPersonalToken(string $personalToken): static { $this->personalToken = $personalToken; return $this; }

    public function getCity(): ?City { return $this->city; }
    public function setCity(?City $city): static { $this->city = $city; return $this; }

    /** @return Collection<int, Team> */
    public function getTeams(): Collection { return $this->teams; }

    /** @return Collection<int, Trip> */
    public function getTrips(): Collection { return $this->trips; }

    /** @return Collection<int, BonusPhoto> */
    public function getBonusPhotos(): Collection { return $this->bonusPhotos; }

    public function isSuperAdmin(): bool { return in_array('ROLE_SUPER_ADMIN', $this->roles, true); }
    public function isAdminCity(): bool { return in_array('ROLE_ADMIN_CITY', $this->roles, true); }

    public function __toString(): string { return $this->getFullName(); }
}
