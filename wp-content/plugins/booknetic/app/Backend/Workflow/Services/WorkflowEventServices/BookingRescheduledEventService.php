<?php

namespace BookneticApp\Backend\Workflow\Services\WorkflowEventServices;

use BookneticApp\Backend\Workflow\DTOs\Request\SaveBookingRescheduledEventRequest;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\Abstracts\BaseEventService;

class BookingRescheduledEventService extends BaseEventService
{
    public function getEventParams(int $id): array
    {
        $data = $this->repository->getWorkflowData($id);
        $selectionData = $this->getCommonSelectionData($data);

        return array_merge($this->getCommonParams($data), [
            'locations' => $selectionData['locations'] ?? [],
            'services' => $selectionData['services'] ?? [],
            'staffs' => $selectionData['staffs'] ?? [],
            'for_each_customer' => $data['for_each_customer'] ?? true,
            'categories' => $selectionData['categories'] ?? [],
        ]);
    }

    public function saveEventData(int $id, SaveBookingRescheduledEventRequest $request): void
    {
        $this->repository->updateDataById($id, [
            'locations' => $request->locations,
            'services' => $request->services,
            'staffs' => $request->staffs,
            'locale' => $request->locale,
            'for_each_customer' => $request->forEachCustomer,
            'called_from' => $request->calledFrom,
            'categories' => $request->categories,
        ]);
    }
}
