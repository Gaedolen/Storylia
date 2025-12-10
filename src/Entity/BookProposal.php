<?php

namespace App\Entity;

use App\Repository\BookProposalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookProposalRepository::class)]
class BookProposal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable:false)]
    private ?Book $book = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable:false)]
    private ?Utilisateur $proposer = null;

    #[ORM\ManyToOne(targetEntity: ClubReadingMonth::class, inversedBy: "bookProposals")]
    #[ORM\JoinColumn(nullable:false)]
    private ?ClubReadingMonth $readingMonth = null;

    #[ORM\Column(type:"datetime_immutable")]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'bookProposal', targetEntity: Vote::class)]
    private Collection $votes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->votes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(Book $book): self
    {
        $this->book = $book;
        return $this;
    }

    public function getProposer(): ?Utilisateur
    {
        return $this->proposer;
    }

    public function setProposer(Utilisateur $proposer): self
    {
        $this->proposer = $proposer;
        return $this;
    }

    public function getReadingMonth(): ?ClubReadingMonth
    {
        return $this->readingMonth;
    }

    public function setReadingMonth(ClubReadingMonth $readingMonth): self
    {
        $this->readingMonth = $readingMonth;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getVotes(): Collection
    {
        return $this->votes;
    }
}
