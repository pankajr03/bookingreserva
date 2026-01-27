<?php

namespace BookneticApp\Backend\Staff\Services;

use BookneticApp\Backend\Base\Repository\HolidayRepository;
use BookneticApp\Backend\Base\Repository\ServiceRepository;
use BookneticApp\Backend\Base\Repository\SpecialDayRepository;
use BookneticApp\Backend\Base\Repository\TimesheetRepository;
use BookneticApp\Backend\Staff\DTOs\Request\StaffRequest;
use BookneticApp\Backend\Staff\Repository\LocationRepository;
use BookneticApp\Backend\Staff\Repository\ServiceStaffRepository;
use BookneticApp\Models\Location;
use BookneticApp\Models\SpecialDay;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Math;

class StaffRelationService
{
    private TimesheetRepository $timesheetRepository;
    private SpecialDayRepository $specialDayRepository;
    private HolidayRepository $holidayRepository;
    private LocationRepository $locationRepository;
    private ServiceRepository $serviceRepository;

    private ServiceStaffRepository $serviceStaffRepository;
    public function __construct()
    {
        $this->timesheetRepository = new TimesheetRepository();
        $this->specialDayRepository = new SpecialDayRepository();
        $this->holidayRepository = new HolidayRepository();
        $this->locationRepository = new LocationRepository();
        $this->serviceRepository = new ServiceRepository();
        $this->serviceStaffRepository = new ServiceStaffRepository();
    }

    /**
     * Save all staff-related data (timesheet, special days, holidays).
     */
    public function saveAll(StaffRequest $dto): void
    {
        $this->saveWeeklySchedule($dto);
        $this->saveSpecialDays($dto);
        $this->saveHolidays($dto);
        $this->saveServices($dto);
    }

    private function saveWeeklySchedule(StaffRequest $dto): void
    {
        if (!empty($dto->weeklySchedule)) {
            $this->timesheetRepository->deleteByStaffId($dto->id);
            $this->timesheetRepository->saveForStaff($dto->id, $dto->weeklySchedule);
        }
    }

    private function saveSpecialDays(StaffRequest $dto): void
    {
        $specialDayIds = [];

        foreach ($dto->specialDays as $day) {
            if (empty($day['date']) || empty($day['start']) || empty($day['end'])) {
                continue;
            }

            $date = Date::dateSQL(Date::reformatDateFromCustomFormat($day['date']));
            $spId = (int)($day['id'] ?? 0);

            $timesheet = json_encode([
                'day_off' => 0,
                'start'   => Date::timeSQL($day['start']),
                'end'     => $day['end'] === '24:00' ? '24:00' : Date::timeSQL($day['end']),
                'breaks'  => array_map(static fn ($b) => [
                    Date::timeSQL($b[0]),
                    $b[1] === '24:00' ? '24:00' : Date::timeSQL($b[1])
                ], $day['breaks'] ?? []),
            ]);

            if ($spId > 0) {
                $this->specialDayRepository->updateByIdAndStaffId($spId, $dto->id, ['date' => $date, 'timesheet' => $timesheet]);
                $specialDayIds[] = $spId;
            } else {
                $this->specialDayRepository->insert([
                    'staff_id' => $dto->id,
                    'date'     => $date,
                    'timesheet' => $timesheet,
                ]);
                $specialDayIds[] = $this->specialDayRepository->getLastInsertedId();
            }
        }

        if (!empty($dto->id) && $dto->isEdit()) {
            $this->specialDayRepository->deleteMissing($dto->id, $specialDayIds);
        }
    }

    private function saveHolidays(StaffRequest $dto): void
    {
        $holidayIds = [];

        foreach ($dto->holidays as $holiday) {
            if (empty($holiday['date'])) {
                continue;
            }

            $holidayId = (int)($holiday['id'] ?? 0);
            $date = Date::dateSQL($holiday['date']);

            if ($holidayId > 0) {
                $holidayIds[] = $holidayId;
            } else {
                $holidayIds[] = $this->holidayRepository->insert($dto->id, $date);
            }
        }

        if (!empty($dto->id) && $dto->isEdit()) {
            $this->holidayRepository->deleteMissing($dto->id, $holidayIds);
        }
    }

    private function saveServices(StaffRequest $dto): void
    {
        $this->serviceStaffRepository->deleteByStaffId($dto->id);

        foreach ($dto->services as $serviceId) {
            $this->serviceStaffRepository->insert(
                $dto->id,
                (int)$serviceId,
                Math::floor(-1),
                Math::floor(-1),
                'percent'
            );
        }
    }

    /**
     * @param int $staffId
     * @return array<SpecialDay>
     */
    public function getSpecialDays(int $staffId): array
    {
        return $this->specialDayRepository->getByStaffId($staffId);
    }

    /**
     * @param int $staffId
     * @return array
     */
    public function getHolidays(int $staffId): array
    {
        return $this->holidayRepository->getByStaffId($staffId);
    }

    /**
     * @param int $staffId
     * @return array<Location|Collection>
     */
    public function getLocations(): array
    {
        return $this->locationRepository->getAllForCurrentUser();
    }

    public function getServices(): array
    {
        return $this->serviceRepository->getAll();
    }
}
