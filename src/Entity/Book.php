<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:255)]
    private ?string $title = null; // Titre principal (français ou VO si unique)

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $voTitle = null; // Titre VO si disponible

    #[ORM\ManyToOne(targetEntity: Author::class, cascade:["persist"])]
    #[ORM\JoinColumn(nullable:false)]
    private ?Author $author = null;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $publicationDate = null;

    #[ORM\Column(type:"json", nullable:true)]
    private ?array $genres = []; // Tableau de genres

    #[ORM\Column(type:"json", nullable:true)]
    private ?array $subjects = []; // Thèmes

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $summary = null;

    #[ORM\Column(type:"string", length:50, unique:true)]
    private ?string $isbn = null;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $cover = null;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $pages = null;

    #[ORM\Column(type:"json", nullable:true)]
    private ?array $publishers = []; // Éditeurs

    #[ORM\Column(type:"string", length:50, nullable:true)]
    private ?string $format = null;

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getVoTitle(): ?string
    {
        return $this->voTitle;
    }

    public function setVoTitle(?string $voTitle): self
    {
        $this->voTitle = $voTitle;
        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getPublicationDate(): ?\DateTimeInterface
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?\DateTimeInterface $publicationDate): self
    {
        $this->publicationDate = $publicationDate;
        return $this;
    }

    public function getGenres(): ?array
    {
        return $this->genres;
    }

    public function setGenres(?array $genres): self
    {
        $this->genres = $genres;
        return $this;
    }

    public function getSubjects(): ?array
    {
        return $this->subjects;
    }

    public function setSubjects(?array $subjects): self
    {
        $this->subjects = $subjects;
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;
        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): self
    {
        $this->isbn = trim($isbn);
        return $this;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(?string $cover): self
    {
        // Si aucune cover dispo, mettre une cover par défaut
        $this->cover = $cover ?? 'images/default_cover.jpg';
        return $this;
    }

    public function getPages(): ?int
    {
        return $this->pages;
    }

    public function setPages(?int $pages): self
    {
        $this->pages = $pages;
        return $this;
    }

    public function getPublishers(): ?array
    {
        return $this->publishers;
    }

    public function setPublishers(?array $publishers): self
    {
        $this->publishers = $publishers;
        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;
        return $this;
    }
}