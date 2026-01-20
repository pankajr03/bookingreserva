<?php

namespace BookneticApp\Backend\Staff\DTOs\Response;

use BookneticApp\Providers\Helpers\Date;

class StaffSpecialDayResponse
{
    private int $id;

    private string $date;

    private array $timesheet;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): StaffSpecialDayResponse
    {
        $this->id = $id;

        return $this;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getDateFormatted(): string
    {
        return empty($this->date) ? '' : Date::datee($this->date);
    }

    public function setDate(string $date): StaffSpecialDayResponse
    {
        $this->date = $date;

        return $this;
    }

    public function getTimesheet(): array
    {
        return $this->timesheet;
    }

    public function setTimesheet(string $timesheet): StaffSpecialDayResponse
    {
        $this->timesheet = json_decode($timesheet, true) ?: [];

        return $this;
    }
}
