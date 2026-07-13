<?php

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $websiteUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(inversedBy: 'partners')]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getWebsiteUrl(): ?string { return $this->websiteUrl; }
    public function setWebsiteUrl(?string $websiteUrl): static { $this->websiteUrl = $websiteUrl; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): static { $this->logo = $logo; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function getCity(): ?City { return $this->city; }
    public function setCity(?City $city): static { $this->city = $city; return $this; }

    public function __toString(): string { return $this->name; }
}
