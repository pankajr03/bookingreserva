<?php

namespace BookneticApp\Backend\Staff\Mappers;

use BookneticApp\Backend\Base\DTOs\Response\SelectOptionResponse;
use BookneticApp\Backend\Staff\DTOs\Response\StaffResponse;
use BookneticApp\Backend\Staff\DTOs\Response\StaffSpecialDayResponse;
use BookneticApp\Models\SpecialDay;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\DB\Collection;

class StaffMapper
{
    /**
     * @param Staff|Collection $staff
     * @return StaffResponse
     */
    public function toResponse(Collection $staff): StaffResponse
    {
        $response = new StaffResponse();

        $response->setId($staff->id);
        $response->setWpUserId($staff->user_id ?: 0);
        $response->setName($staff->name ?: '');
        $response->setEmail($staff->email ?: '');
        $response->setPhone($staff->phone_number ?: '');
        $response->setProfession($staff->profession ?: '');
        $response->setAbout($staff->about ?: '');
        $response->setLocations($staff->locations ?: '');
        $response->setIsActive((bool) $staff->is_active);

        return $response;
    }

    /**
     * @param array<object{id:int,name:string}> $items
     * @return array<SelectOptionResponse>
     */
    public function toSelectOptionResponseList(array $items): array
    {
        return array_map(static fn ($item) => new SelectOptionResponse($item->id, $item->name), $items);
    }

    /**
     * @param array<SpecialDay|Collection> $specialDays
     * @return array<StaffSpecialDayResponse>
     */
    public function toSpecialDayListResponse(array $specialDays): array
    {
        return array_map([$this, 'toSpecialDayResponse'], $specialDays);
    }

    /**
     * @param SpecialDay|Collection $specialDay
     * @return StaffSpecialDayResponse
     */
    public function toSpecialDayResponse(Collection $specialDay): StaffSpecialDayResponse
    {
        $response = new StaffSpecialDayResponse();

        $response->setId($specialDay->id);
        $response->setDate($specialDay->date);
        $response->setTimesheet($specialDay->timesheet);

        return $response;
    }
}
