<?php

namespace App\Entity;

use App\Repository\BookshelfRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookshelfRepository::class)]
class Bookshelf
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'bookshelves')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    #[ORM\ManyToOne(targetEntity: ReadingStatus::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ReadingStatus $readingStatus = null;

    // Getters et setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
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

    public function getReadingStatus(): ?ReadingStatus
    {
        return $this->readingStatus;
    }

    public function setReadingStatus(?ReadingStatus $readingStatus): static
    {
        $this->readingStatus = $readingStatus;
        return $this;
    }
}
