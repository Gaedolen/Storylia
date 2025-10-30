<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubRepository::class)]
class Club
{
    public const STATUS_ACTIF = 'actif';
    public const STATUS_INACTIF = 'inactif';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $creationDate = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_ACTIF;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'clubsCrees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $creator = null;

    #[ORM\ManyToMany(targetEntity: Utilisateur::class, mappedBy: 'clubsMembre')]
    private Collection $membres;

    public function __construct()
    {
        $this->creationDate = new \DateTime();
        $this->status = self::STATUS_ACTIF;
        $this->membres = new ArrayCollection();
    }

    // Getters et setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTime $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, [self::STATUS_ACTIF, self::STATUS_INACTIF])) {
            throw new \InvalidArgumentException("Statut invalide pour le club");
        }
        $this->status = $status;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->status === self::STATUS_ACTIF;
    }

    public function isInactif(): bool
    {
        return $this->status === self::STATUS_INACTIF;
    }

    public function getCreator(): ?Utilisateur
    {
        return $this->creator;
    }

    public function setCreator(?Utilisateur $creator): static
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    public function addMembre(Utilisateur $utilisateur): self
    {
        if (!$this->membres->contains($utilisateur)) {
            $this->membres[] = $utilisateur;
            $utilisateur->addClubMembre($this);
        }
        return $this;
    }

    public function removeMembre(Utilisateur $utilisateur): self
    {
        if ($this->membres->removeElement($utilisateur)) {
            $utilisateur->removeClubMembre($this);
        }
        return $this;
    }
}
