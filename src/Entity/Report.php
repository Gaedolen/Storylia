<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(
    uniqueConstraints: [
        new ORM\UniqueConstraint(columns: ['author_id', 'reported_id'])
    ]
)]
class Report
{
    public const STATUS_EN_COURS = 'en_cours';
    public const STATUS_TRAITE = 'traite';
    public const STATUS_REFUSE = 'refuse';
    public const STATUS_ADMIN = 'transmis_admin';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 50)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $employeMessage = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    private ?Utilisateur $transmittedBy = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_EN_COURS;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'signalementsFaits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $author = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'signalementsRecus')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateur $reported = null;

    #[ORM\ManyToOne(targetEntity: Review::class, inversedBy: 'reports')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Review $review = null;

    #[ORM\ManyToOne(targetEntity: Club::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Club $reportedClub = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Book $reportedBook = null;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->status = self::STATUS_EN_COURS;
    }

    // Getters et setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getEmployeMessage(): ?string
    {
        return $this->employeMessage;
    }

    public function setEmployeMessage(?string $employeMessage): self
    {
        $this->employeMessage = $employeMessage;
        return $this;
    }

    public function getTransmittedBy(): ?Utilisateur
    {
        return $this->transmittedBy;
    }

    public function setTransmittedBy(?Utilisateur $user): self
    {
        $this->transmittedBy = $user;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, [
            self::STATUS_EN_COURS,
            self::STATUS_TRAITE,
            self::STATUS_REFUSE,
            self::STATUS_ADMIN
        ])) {
            throw new \InvalidArgumentException("Statut invalide pour le signalement");
        }
        $this->status = $status;
        return $this;
    }

    public function isEnCours(): bool
    {
        return $this->status === self::STATUS_EN_COURS;
    }

    public function isTraite(): bool
    {
        return $this->status === self::STATUS_TRAITE;
    }

    public function isRefuse(): bool
    {
        return $this->status === self::STATUS_REFUSE;
    }

    public function getAuthor(): ?Utilisateur
    {
        return $this->author;
    }

    public function setAuthor(?Utilisateur $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getReported(): ?Utilisateur
    {
        return $this->reported;
    }

    public function setReported(?Utilisateur $reported): static
    {
        $this->reported = $reported;
        return $this;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): static
    {
        $this->review = $review;
        return $this;
    }

    public function getReportedClub(): ?Club
    {
        return $this->reportedClub;
    }

    public function setReportedClub(?Club $club): static
    {
        $this->reportedClub = $club;
        return $this;
    }

    public function getReportedBook(): ?Book
    {
        return $this->reportedBook;
    }

    public function setReportedBook(?Book $book): static
    {
        $this->reportedBook = $book;
        return $this;
    }
}
