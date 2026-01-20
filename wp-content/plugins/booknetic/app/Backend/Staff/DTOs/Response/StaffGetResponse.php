<?php

namespace BookneticApp\Backend\Staff\DTOs\Response;

use BookneticApp\Backend\Base\DTOs\Response\SelectOptionResponse;

class StaffGetResponse
{
    private int $id = 0;

    private StaffResponse $staff;

    /**
     * @var array<int>
     */
    private array $selectedServices = [];

    private array $timesheet = [];

    private bool $hasSpecificTimesheet = false;

    /**
     * @var array<StaffSpecialDayResponse>
     */
    private array $specialDays = [];

    private string $holidays = '';

    /**
     * @var array<SelectOptionResponse>
     */
    private array $locations = [];

    /**
     * @var array<SelectOptionResponse>
     */
    private array $services = [];

    /**
     * @var array<StaffWpUserSelectOptionResponse>
     */
    private array $users = [];

    private string $defaultCountryCode = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): StaffGetResponse
    {
        $this->id = $id;

        return $this;
    }

    public function getStaff(): StaffResponse
    {
        return $this->staff;
    }

    public function setStaff(StaffResponse $staff): StaffGetResponse
    {
        $this->staff = $staff;

        return $this;
    }

    public function getSelectedServices(): array
    {
        return $this->selectedServices;
    }

    public function setSelectedServices(array $selectedServices): StaffGetResponse
    {
        $this->selectedServices = $selectedServices;

        return $this;
    }

    public function getTimesheet(): array
    {
        return $this->timesheet;
    }

    public function setTimesheet(array $timesheet): StaffGetResponse
    {
        $this->timesheet = $timesheet;

        return $this;
    }

    public function hasSpecificTimesheet(): bool
    {
        return $this->hasSpecificTimesheet;
    }

    public function setHasSpecificTimesheet(bool $hasSpecificTimesheet): StaffGetResponse
    {
        $this->hasSpecificTimesheet = $hasSpecificTimesheet;

        return $this;
    }

    public function getSpecialDays(): array
    {
        return $this->specialDays;
    }

    public function setSpecialDays(array $specialDays): StaffGetResponse
    {
        $this->specialDays = $specialDays;

        return $this;
    }

    public function getHolidays(): string
    {
        return $this->holidays;
    }

    public function setHolidays(string $holidays): StaffGetResponse
    {
        $this->holidays = $holidays;

        return $this;
    }

    public function getLocations(): array
    {
        return $this->locations;
    }

    public function setLocations(array $locations): StaffGetResponse
    {
        $this->locations = $locations;

        return $this;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function setServices(array $services): StaffGetResponse
    {
        $this->services = $services;

        return $this;
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function setUsers(array $users): StaffGetResponse
    {
        $this->users = $users;

        return $this;
    }

    public function getDefaultCountryCode(): string
    {
        return $this->defaultCountryCode;
    }

    public function setDefaultCountryCode(string $defaultCountryCode): StaffGetResponse
    {
        $this->defaultCountryCode = $defaultCountryCode;

        return $this;
    }
}
