<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubRepository::class)]
#[UniqueEntity(
    fields: ['name'],
    message: 'Ce nom de club existe déjà.'
)]
class Club
{
    public const STATUS_ACTIF = 'actif';
    public const STATUS_INACTIF = 'inactif';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le nom du club est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Le nom doit faire au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;


    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez sélectionner au moins une préférence.")]
    private array $preferences = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $creationDate = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_ACTIF;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $suspendReason = null;

    #[ORM\OneToMany(mappedBy: "club", targetEntity: Book::class)]
    private Collection $books;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'clubsCrees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $creator = null;

    #[ORM\ManyToMany(targetEntity: Utilisateur::class, mappedBy: 'clubsMembre')]
    private Collection $membres;

    #[ORM\OneToMany(mappedBy: 'club', targetEntity: ClubReadingMonth::class, cascade: ['remove'])]
    private Collection $readingMonths;

    public function __construct()
    {
        $this->creationDate = new \DateTime();
        $this->status = self::STATUS_ACTIF;
        $this->membres = new ArrayCollection();
        $this->readingMonths = new ArrayCollection();
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

    public function getPreferences(): array
    {
        return $this->preferences;
    }

    public function setPreferences(array $preferences): self
    {
        $this->preferences = $preferences;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
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

    public function getSuspendReason(): ?string
    {
        return $this->suspendReason;
    }

    public function setSuspendReason(?string $reason): static
    {
        $this->suspendReason = $reason;
        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBooks(): Collection
    {
        return $this->books ?? new ArrayCollection();
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

    /**
     * @return Collection<int, ClubReadingMonth>
     */
    public function getReadingMonths(): Collection
    {
        return $this->readingMonths;
    }

    public function addReadingMonth(ClubReadingMonth $readingMonth): self
    {
        if (!$this->readingMonths->contains($readingMonth)) {
            $this->readingMonths->add($readingMonth);
            $readingMonth->setClub($this);
        }
        return $this;
    }

    public function removeReadingMonth(ClubReadingMonth $readingMonth): self
    {
        if ($this->readingMonths->removeElement($readingMonth)) {
            if ($readingMonth->getClub() === $this) {
                $readingMonth->setClub(null);
            }
        }
        return $this;
    }
}
