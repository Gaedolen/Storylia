<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\MessageRepository;
use App\Entity\Utilisateur;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $sender = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $receiver = null;

    #[ORM\Column(type:"text")]
    private ?string $content = null;

    #[ORM\Column(type:"datetime")]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ─── Getters & Setters ───

    public function getId(): ?int 
    { 
        return $this->id; 
    }
    public function getSender(): ?Utilisateur
    { 
        return $this->sender; 
    }
    public function setSender(Utilisateur $sender): self 
    {
         $this->sender = $sender; return $this; 
        }

    public function getReceiver(): ?Utilisateur 
    { 
        return $this->receiver; 
    }
    public function setReceiver(Utilisateur $receiver): self 
    { 
        $this->receiver = $receiver; return $this; 
    }

    public function getContent(): ?string 
    { return $this->content; 
    }
    public function setContent(string $content): self 
    { 
        $this->content = $content; return $this; 
    }

    public function getCreatedAt(): ?\DateTimeInterface 
    { 
        return $this->createdAt; 
    }
    public function setCreatedAt(\DateTimeInterface $createdAt): self 
    { 
        $this->createdAt = $createdAt; return $this; 
    }
}
