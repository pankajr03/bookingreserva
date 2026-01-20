<?php

namespace BookneticApp\Backend\Base\Repository;

use BookneticApp\Models\Timesheet;

class TimesheetRepository
{
    /**
     * Insert or replace a staff timesheet record.
     *
     * @param int $staffId
     * @param array $weeklySchedule
     * @return void
     */
    public function saveForStaff(int $staffId, array $weeklySchedule): void
    {
        Timesheet::query()->insert([
            'staff_id'  => $staffId,
            'timesheet' => json_encode($weeklySchedule),
        ]);
    }

    /**
     * Delete all timesheets belonging to a specific staff member.
     *
     * @param int $staffId
     * @return void
     */
    public function deleteByStaffId(int $staffId): void
    {
        Timesheet::query()->where('staff_id', $staffId)->delete();
    }
}
