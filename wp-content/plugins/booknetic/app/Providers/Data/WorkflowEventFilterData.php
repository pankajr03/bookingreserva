<?php

namespace BookneticApp\Providers\Data;

class WorkflowEventFilterData
{
    private ?string $locale = null;
    private array $statuses = [];
    private array $prevStatuses = [];
    private array $categories = [];
    private array $locations = [];
    private array $services = [];
    private array $staffs = [];
    private ?string $calledFrom = null;

    public static function fromArray(?array $data): self
    {
        $instance = new self();

        if ($data === null) {
            return $instance;
        }

        $instance->locale = isset($data['locale']) && is_string($data['locale']) ? $data['locale'] : null;
        $instance->statuses = is_array($data['statuses'] ?? null) ? $data['statuses'] : [];
        $instance->prevStatuses = is_array($data['prev_statuses'] ?? null) ? $data['prev_statuses'] : [];
        $instance->categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
        $instance->locations = is_array($data['locations'] ?? null) ? $data['locations'] : [];
        $instance->services = is_array($data['services'] ?? null) ? $data['services'] : [];
        $instance->staffs = is_array($data['staffs'] ?? null) ? $data['staffs'] : [];
        $instance->calledFrom = isset($data['called_from']) && is_string($data['called_from']) ? $data['called_from'] : null;

        return $instance;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function getPrevStatuses(): array
    {
        return $this->prevStatuses;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getLocations(): array
    {
        return $this->locations;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getStaffs(): array
    {
        return $this->staffs;
    }

    public function getCalledFrom(): ?string
    {
        return $this->calledFrom;
    }

    public function hasLocale(): bool
    {
        return $this->locale !== null && $this->locale !== '';
    }

    public function hasStatuses(): bool
    {
        return count($this->statuses) > 0;
    }

    public function hasPrevStatuses(): bool
    {
        return count($this->prevStatuses) > 0;
    }

    public function hasCategories(): bool
    {
        return count($this->categories) > 0;
    }

    public function hasLocations(): bool
    {
        return count($this->locations) > 0;
    }

    public function hasServices(): bool
    {
        return count($this->services) > 0;
    }

    public function hasStaffs(): bool
    {
        return count($this->staffs) > 0;
    }

    public function hasCalledFrom(): bool
    {
        return $this->calledFrom !== null && $this->calledFrom !== '';
    }

    public function isCalledFromBackend(): bool
    {
        return $this->calledFrom === 'backend';
    }

    public function isCalledFromFrontend(): bool
    {
        return $this->calledFrom === 'frontend';
    }

    public function matchesStatus(string $status): bool
    {
        return in_array($status, $this->statuses);
    }

    public function matchesPrevStatus(string $status): bool
    {
        return in_array($status, $this->prevStatuses);
    }

    public function matchesCategory(int $categoryId): bool
    {
        return in_array($categoryId, $this->categories);
    }

    public function matchesLocation(int $locationId): bool
    {
        return in_array($locationId, $this->locations);
    }

    public function matchesService(int $serviceId): bool
    {
        return in_array($serviceId, $this->services);
    }

    public function matchesStaff(int $staffId): bool
    {
        return in_array($staffId, $this->staffs);
    }
}
