<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: "logs")]
#[MongoDB\Index(keys: ['createdAt' => 'desc'])]
class Log
{
    #[MongoDB\Id]
    private $id;

    #[MongoDB\Field(type: "string")]
    private string $userId;

    #[MongoDB\Field(type: "string")]
    private string $action;

    #[MongoDB\Field(type: "string")]
    private string $details;

    #[MongoDB\Field(type: "date")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getDetails(): string
    {
        return $this->details;
    }

    public function setDetails(string $details): void
    {
        $this->details = $details;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
