<?php

namespace BookneticApp\Backend\Workflow\Services\WorkflowEventServices;

use BookneticApp\Backend\Workflow\DTOs\Request\SaveBookingStatusChangedEventRequest;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\Abstracts\BaseEventService;

class BookingStatusChangedEventService extends BaseEventService
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
            'locale' => $data['locale'] ?? get_locale(),
            'locales' => $this->getLocales(),
            'statuses' => $data['statuses'] ?? [],
            'prev_statuses' => $data['prev_statuses'] ?? [],
            'called_from' => $data['called_from'] ?? '',
            'call_from' => [
                'both' => bkntc__('Both'),
                'backend' => bkntc__('Backend'),
                'frontend' => bkntc__('Frontend'),
            ],
        ];
    }

    public function saveEventData(int $id, SaveBookingStatusChangedEventRequest $request): void
    {
        $this->repository->updateDataById($id, [
            'statuses' => $request->statuses,
            'prev_statuses' => $request->prevStatuses,
            'locale' => $request->locale,
            'called_from' => $request->calledFrom,
            'locations' => $request->locations,
            'services' => $request->services,
            'staffs' => $request->staffs,
            'categories' => $request->categories,
        ]);
    }
}
