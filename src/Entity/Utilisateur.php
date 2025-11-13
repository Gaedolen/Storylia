<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[UniqueEntity(fields: ['pseudo'], message: 'Ce pseudo est déjà utilisé. Veuillez en choisir un autre.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_ACTIF = 'actif';
    public const STATUS_SUSPENDU = 'suspendu';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $familyName = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le pseudo est obligatoire.')]
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
    #[Assert\NotBlank(message: 'La date de naissance est obligatoire.')]
    #[Assert\LessThanOrEqual(
        value: '-16 years',
        message: 'Vous devez avoir au moins 16 ans pour vous inscrire.'
    )]
    private ?\DateTime $birthDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $presentation = null;

    #[ORM\Column(type: "json", nullable: true)]
    private array $preferences = [];

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_ACTIF;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'utilisateurs')]
    private ?Role $role = null;

    /** 
     * @var Collection<int, Club> 
    */
    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Club::class)]
    private Collection $clubsCrees;

    #[ORM\ManyToMany(targetEntity: Club::class, inversedBy: 'membres')]
    #[ORM\JoinTable(name: 'utilisateur_club')]
    private Collection $clubsMembre;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Bookshelf::class)]
    private Collection $bookshelves;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Review::class, cascade: ['remove'])]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Report::class)]
    private Collection $signalementsFaits;

    #[ORM\OneToMany(mappedBy: 'reported', targetEntity: Report::class)]
    private Collection $signalementsRecus;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: ReadingHistory::class)]
    private Collection $readingHistory;

    public function __construct()
    {
        $this->clubsCrees = new ArrayCollection();
        $this->clubsMembre = new ArrayCollection();
        $this->bookshelves = new ArrayCollection();
        $this->reviews = new ArrayCollection();
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

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getPresentation(): ?string
    {
        return $this->presentation;
    }

    public function setPresentation(?string $presentation): self
    {
        $this->presentation = $presentation;
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

    public function getRoles(): array
    {
        $roles = [];
        if ($this->role) {
            $roles[] = $this->role->getLabel();
        }
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
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

    /**
     * @return Collection<int, Club>
     */
    public function getClubsMembre(): Collection
    {
        return $this->clubsMembre;
    }

    public function addClubMembre(Club $club): self
    {
        if (!$this->clubsMembre->contains($club)) {
            $this->clubsMembre[] = $club;
            $club->addMembre($this);
        }
        return $this;
    }

    public function removeClubMembre(Club $club): self
    {
        if ($this->clubsMembre->removeElement($club)) {
            $club->removeMembre($this);
        }
        return $this;
    }

    public function getBookshelves(): Collection 
    { 
        return $this->bookshelves; 
    }

    public function addBookshelf(Bookshelf $bookshelf): self
    {
        if (!$this->bookshelves->contains($bookshelf)) {
            $this->bookshelves[] = $bookshelf;
            $bookshelf->setUtilisateur($this);
        }
        return $this;
    }

    public function removeBookshelf(Bookshelf $bookshelf): self
    {
        if ($this->bookshelves->removeElement($bookshelf)) {
            if ($bookshelf->getUtilisateur() === $this) {
                $bookshelf->setUtilisateur(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setUtilisateur($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
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

    /**
     * @return Collection<int, ReadingHistory>
     */
    public function getReadingHistory(): Collection
    {
        return $this->readingHistory;
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function getSalt(): ?string
    {
        return null;
    }
}
