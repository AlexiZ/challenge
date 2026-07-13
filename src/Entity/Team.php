<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(length: 150)]
    private string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isPublic = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\ManyToOne(inversedBy: 'teams')]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams')]
    #[ORM\JoinTable(name: 'team_members')]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function isPublic(): bool { return $this->isPublic; }
    public function setIsPublic(bool $isPublic): static { $this->isPublic = $isPublic; return $this; }

    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }

    public function getCity(): ?City { return $this->city; }
    public function setCity(?City $city): static { $this->city = $city; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, User> */
    public function getMembers(): Collection { return $this->members; }

    public function addMember(User $user): static
    {
        if (!$this->members->contains($user)) {
            $this->members->add($user);
        }
        return $this;
    }

    public function removeMember(User $user): static
    {
        $this->members->removeElement($user);
        return $this;
    }

    public function hasMember(User $user): bool
    {
        return $this->members->contains($user);
    }

    public function getMemberCount(): int { return $this->members->count(); }

    public function getInitials(): string
    {
        $skip = ['de', 'du', 'en', 'et', 'la', 'le', 'les', 'à', 'au', 'aux'];
        $initials = '';
        foreach (preg_split('/\s+/', $this->name) as $word) {
            if ($word === '' || in_array(mb_strtolower($word), $skip, true)) continue;
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            if (mb_strlen($initials) >= 3) break;
        }
        return $initials ?: mb_strtoupper(mb_substr($this->name, 0, 2));
    }

    public function __toString(): string { return $this->name; }
}
