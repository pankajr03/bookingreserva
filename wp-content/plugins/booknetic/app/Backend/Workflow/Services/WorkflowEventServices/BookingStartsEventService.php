<?php

namespace BookneticApp\Backend\Workflow\Services\WorkflowEventServices;

use BookneticApp\Backend\Workflow\DTOs\Request\SaveBookingStartsEventRequest;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\Abstracts\BaseEventService;

class BookingStartsEventService extends BaseEventService
{
    public function getEventParams(int $id): array
    {
        $data = $this->repository->getWorkflowData($id);
        $selectionData = $this->getCommonSelectionData($data);

        return [
            'locations' => $selectionData['locations'] ?? [],
            'services' => $selectionData['services'] ?? [],
            'staffs' => $selectionData['staffs'] ?? [],
            'categories' => $selectionData['categories'] ?? [],
            'locale' => $data['locale'] ?? '',
            'locales' => $this->getLocales(),
            'offset_sign' => $data['offset_sign'] ?? 'before',
            'offset_value' => $data['offset_value'] ?? 0,
            'offset_type' => $data['offset_type'] ?? 'minute',
            'statuses' => $data['statuses'] ?? [],
            'for_each_customer' => $data['for_each_customer'] ?? true,
        ];
    }

    public function saveEventData(int $id, SaveBookingStartsEventRequest $request): void
    {
        $this->repository->updateDataById($id, [
            'offset_sign' => $request->offsetSign,
            'offset_value' => $request->offsetValue,
            'offset_type' => $request->offsetType,
            'statuses' => $request->statuses,
            'locations' => $request->locations,
            'services' => $request->services,
            'staffs' => $request->staffs,
            'locale' => $request->locale,
            'for_each_customer' => $request->forEachCustomer,
            'categories' => $request->categories
        ]);
    }
}
