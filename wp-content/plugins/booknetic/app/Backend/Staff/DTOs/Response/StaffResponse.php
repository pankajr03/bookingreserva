<?php

namespace BookneticApp\Backend\Staff\DTOs\Response;

class StaffResponse
{
    private int $id = 0;

    private int $wpUserId = 0;

    private string $name = '';

    private string $email = '';

    private string $profession = '';

    private string $phone = '';

    private string $about = '';

    private array $locations = [];

    private bool $isActive = true;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): StaffResponse
    {
        $this->id = $id;

        return $this;
    }

    public function getWpUserId(): int
    {
        return $this->wpUserId;
    }

    public function setWpUserId(int $wpUserId): StaffResponse
    {
        $this->wpUserId = $wpUserId ?: 0;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): StaffResponse
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): StaffResponse
    {
        $this->email = $email;

        return $this;
    }

    public function getProfession(): string
    {
        return $this->profession;
    }

    public function setProfession(string $profession): StaffResponse
    {
        $this->profession = $profession;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): StaffResponse
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAbout(): string
    {
        return $this->about;
    }

    public function setAbout(string $about): StaffResponse
    {
        $this->about = $about;

        return $this;
    }

    public function getLocations(): array
    {
        return $this->locations;
    }

    public function setLocations(string $locations): StaffResponse
    {
        if (empty($locations)) {
            $this->locations = [];

            return $this;
        }

        $this->locations = explode(',', $locations) ?: [];

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): StaffResponse
    {
        $this->isActive = $isActive;

        return $this;
    }
}
