<?php

namespace App\Entity;

use App\Repository\ClubReadingMonthRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubReadingMonthRepository::class)]
class ClubReadingMonth
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Exemple : "2025-04" ou une DateTime du 1er jour du mois
    #[ORM\Column(length: 7)]
    private ?string $month = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'readingMonths')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Club $club = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    #[ORM\OneToMany(mappedBy: 'readingMonth', targetEntity: ClubReview::class, cascade: ['remove'])]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'readingMonth', targetEntity: ClubMessage::class, cascade: ['remove'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->reviews = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMonth(): ?string
    {
        return $this->month;
    }

    public function setMonth(string $month): static
    {
        $this->month = $month;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): static
    {
        $this->club = $club;
        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;
        return $this;
    }

    /**
     * @return Collection<int, ClubReview>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(ClubReview $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setReadingMonth($this);
        }

        return $this;
    }

    public function removeReview(ClubReview $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getReadingMonth() === $this) {
                $review->setReadingMonth(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(ClubMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setReadingMonth($this);
        }

        return $this;
    }

    public function removeMessage(ClubMessage $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getReadingMonth() === $this) {
                $message->setReadingMonth(null);
            }
        }

        return $this;
    }
}
