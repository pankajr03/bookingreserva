<?php

namespace BookneticApp\Backend\Appointments\Helpers\Data;

final class WorkflowFilterData
{
    private int $offset = 0;
    private array $statuses = [];
    private array $locations = [];
    private array $services = [];
    private array $staffs = [];
    private ?string $gender = null;
    private array $years = [];
    private array $months = [];
    private string $time = '';
    private ?string $locale = null;

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): WorkflowFilterData
    {
        $this->offset = $offset;

        return $this;
    }

    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function setStatuses(array $statuses): WorkflowFilterData
    {
        $this->statuses = $statuses;

        return $this;
    }

    public function getLocations(): array
    {
        return $this->locations;
    }

    public function setLocations(array $locations): WorkflowFilterData
    {
        $this->locations = $locations;

        return $this;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function setServices(array $services): WorkflowFilterData
    {
        $this->services = $services;

        return $this;
    }

    public function getStaffs(): array
    {
        return $this->staffs;
    }

    public function setStaffs(array $staffs): WorkflowFilterData
    {
        $this->staffs = $staffs;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): WorkflowFilterData
    {
        $this->gender = $gender;

        return $this;
    }

    public function getYears(): array
    {
        return $this->years;
    }

    public function setYears(array $years): WorkflowFilterData
    {
        $this->years = $years;

        return $this;
    }

    public function getMonths(): array
    {
        return $this->months;
    }

    public function setMonths(array $months): WorkflowFilterData
    {
        $this->months = $months;

        return $this;
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function setTime(string $time): WorkflowFilterData
    {
        $this->time = $time;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): WorkflowFilterData
    {
        $this->locale = $locale;

        return $this;
    }
}
