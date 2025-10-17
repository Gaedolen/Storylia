<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur
{
    public const STATUS_ACTIF = 'actif';
    public const STATUS_SUSPENDU = 'suspendu';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $familyName = null;

    #[ORM\Column(length: 50)]
    private ?string $firstName = null;

    #[ORM\Column(length: 50)]
    private ?string $pseudo = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L’email est obligatoire.')]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide.")] 
    private ?string $email = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $birthDate = null;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_ACTIF;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'utilisateurs')]
    private ?Role $role = null;

    #[ORM\OneToMany(mappedBy: 'createur', targetEntity: Club::class)]
    private Collection $clubsCrees;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Bookshelf::class)]
    private Collection $bookshelfs;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Review::class)]
    private Collection $review;

    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Report::class)]
    private Collection $signalementsFaits;

    #[ORM\OneToMany(mappedBy: 'signale', targetEntity: Report::class)]
    private Collection $signalementsRecus;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: ReadingHistory::class)]
    private Collection $readingHistory;

    public function __construct()
    {
        $this->clubsCrees = new ArrayCollection();
        $this->bookshelfs = new ArrayCollection();
        $this->review = new ArrayCollection();
        $this->signalementsFaits = new ArrayCollection();
        $this->signalementsRecus = new ArrayCollection();
        $this->readingHistory = new ArrayCollection();
    }

    // Setters et Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(string $familyName): static
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_ACTIF, self::STATUS_SUSPENDU])) {
            throw new \InvalidArgumentException("Statut utilisateur invalide");
        }

        $this->status = $status;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->status === self::STATUS_ACTIF;
    }

    public function isSuspendu(): bool
    {
        return $this->status === self::STATUS_SUSPENDU;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getClubsCrees(): Collection
    {
        return $this->clubsCrees;
    }

    public function addClubCrees(Club $club): self
    {
        if (!$this->clubsCrees->contains($club)) {
            $this->clubsCrees[] = $club;
            $club->setCreator($this);
        }
        return $this;
    }

    public function removeClubCrees(Club $club): self
    {
        if ($this->clubsCrees->removeElement($club)) {
            if ($club->getCreator() === $this) {
                $club->setCreator(null);
            }
        }
        return $this;
    }

    public function getBookshelfs(): Collection 
    { 
        return $this->bookshelfs; 
    }

    public function addBookshelf(Bookshelf $bookshelf): self
    {
        if (!$this->bookshelfs->contains($bookshelf)) {
            $this->bookshelfs[] = $bookshelf;
            $bookshelf->setUtilisateur($this);
        }
        return $this;
    }

    public function removeBookshelf(Bookshelf $bookshelf): self
    {
        if ($this->bookshelfs->removeElement($bookshelf)) {
            if ($bookshelf->getUtilisateur() === $this) {
                $bookshelf->setUtilisateur(null);
            }
        }
        return $this;
    }

    public function getReview(): Collection 
    { 
        return $this->review; 
    }

    public function addReview(Review $review): self
    {
        if (!$this->review->contains($review)) {
            $this->review[] = $review;
            $review->setUtilisateur($this);
        }
        return $this;
    }

    public function removeReview(Review $review): self
    {
        if ($this->review->removeElement($review)) {
            if ($review->getUtilisateur() === $this) {
                $review->setUtilisateur(null);
            }
        }
        return $this;
    }

    public function getSignalementsFaits(): Collection 
    { 
        return $this->signalementsFaits; 
    }

    public function addSignalementsFaits(Report $report): self
    {
        if (!$this->signalementsFaits->contains($report)) {
            $this->signalementsFaits[] = $report;
            $report->setAuthor($this);
        }
        return $this;
    }

    public function removeSignalementsFaits(Report $report): self
    {
        if ($this->signalementsFaits->removeElement($report)) {
            if ($report->getAuthor() === $this) {
                $report->setAuthor(null);
            }
        }
        return $this;
    }

    public function getSignalementsRecus(): Collection 
    { 
        return $this->signalementsRecus; 
    }

    public function addSignalementsRecus(Report $report): self
    {
        if (!$this->signalementsRecus->contains($report)) {
            $this->signalementsRecus[] = $report;
            $report->setReported($this);
        }
        return $this;
    }

    public function removeSignalementsRecus(Report $report): self
    {
        if ($this->signalementsRecus->removeElement($report)) {
            if ($report->getReported() === $this) {
                $report->setReported(null);
            }
        }
        return $this;
    }

    public function addReadingHistory(ReadingHistory $history): self
    {
        if (!$this->readingHistory->contains($history)) {
            $this->readingHistory[] = $history;
            $history->setUtilisateur($this);
        }
        return $this;
    }

    public function removeReadingHistory(ReadingHistory $history): self
    {
        if ($this->readingHistory->removeElement($history)) {
            if ($history->getUtilisateur() === $this) {
                $history->setUtilisateur(null);
            }
        }
        return $this;
    }
}
