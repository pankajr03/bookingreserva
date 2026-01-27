<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Providers\Helpers\Date;

class TimeSheetObject implements \JsonSerializable
{
    use TimeSheetObjectJsonSerialize;

    private array $timesheet;

    public function __construct(array $timesheet = [])
    {
        $default = [
            'start'                 =>  '',
            'end'                   =>  '',
            'day_off'               =>  1,
            'breaks'                =>  [],
            'holiday'               =>  0,
            'special_timesheet'     =>  0
        ];

        $this->timesheet = array_merge($default, $timesheet);
    }

    public function isDayOff(): bool
    {
        return $this->timesheet['day_off'] == 1;
    }

    public function isHoliday(): bool
    {
        return $this->timesheet['holiday'] == 1;
    }

    public function isSpecialTimesheet(): bool
    {
        return $this->timesheet['special_timesheet'] == 1;
    }

    public function startTime(): string
    {
        $start = $this->timesheet['start'];

        return Date::timeSQL($start);
    }

    public function endTime(): string
    {
        $end = $this->timesheet['end'];

        if ($end == '24:00') {
            return '24:00';
        }

        return Date::timeSQL($end);
    }

    /**
     * @return BreakTimeObject[]
     */
    public function breaks(): array
    {
        $breaks = [];

        foreach ($this->timesheet['breaks'] as $breakTime) {
            $breaks[] = new BreakTimeObject($breakTime);
        }

        return $breaks;
    }

    public function toArr(): array
    {
        return $this->timesheet;
    }
}
