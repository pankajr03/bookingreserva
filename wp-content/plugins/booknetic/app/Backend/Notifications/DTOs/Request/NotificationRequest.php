<?php

namespace BookneticApp\Backend\Notifications\DTOs\Request;

class NotificationRequest
{
    private int $userId;
    private string $type;
    private string $title;
    private string $message;
    private string $actionType;
    private string $actionData;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): void
    {
        $this->actionType = $actionType;
    }

    public function getActionData(): string
    {
        return $this->actionData;
    }

    public function setActionData(string $actionData): void
    {
        $this->actionData = $actionData;
    }
}
