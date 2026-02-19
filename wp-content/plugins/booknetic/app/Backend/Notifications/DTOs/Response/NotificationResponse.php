<?php

namespace BookneticApp\Backend\Notifications\DTOs\Response;

class NotificationResponse implements \JsonSerializable
{
    private int $id;
    private int $userId;
    private string $type;
    private string $title;
    private string $message;
    private string $actionType;
    private string $actionData;
    private ?string $readAt = null;
    private string $createdAt;
    private ?string $userLogin = null;

    public function getReadAt(): ?string
    {
        return $this->readAt;
    }

    public function setReadAt(?string $readAt): void
    {
        $this->readAt = $readAt;
    }

    public function getActionData(): string
    {
        return $this->actionData;
    }

    public function setActionData(string $actionData): void
    {
        $this->actionData = $actionData;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): void
    {
        $this->actionType = $actionType;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserLogin(): ?string
    {
        return $this->userLogin;
    }

    public function setUserLogin(?string $userLogin): void
    {
        $this->userLogin = $userLogin;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'type' => $this->getType(),
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'action_type' => $this->getActionType(),
            'action_data' => $this->getActionData(),
            'read_at' => $this->getReadAt(),
            'user_login' => $this->getUserLogin(),
            'created_at' => $this->getCreatedAt(),
        ];
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
