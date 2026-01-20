<?php

namespace BookneticApp\Backend\Workflow\Services\WorkflowEventServices;

use BookneticApp\Backend\Workflow\DTOs\Request\SaveNewBookingEventRequest;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\Abstracts\BaseEventService;
use BookneticApp\Providers\Helpers\Helper;

class NewBookingEventService extends BaseEventService
{
    public function getEventParams(int $id): array
    {
        $data = $this->repository->getWorkflowData($id);
        $selectionData = $this->getCommonSelectionData($data);

        /** @var array $params */
        $params = array_merge($this->getCommonParams($data), [
            'locations' => $selectionData['locations'] ?? [],
            'services' => $selectionData['services'] ?? [],
            'staffs' => $selectionData['staffs'] ?? [],
            'categories' => $selectionData['categories'] ?? [],
            'statuses' => [],
        ]);

        if (!empty($data) && isset($data['statuses']) && is_array($data['statuses'])) {
            $appointmentStatuses = Helper::getAppointmentStatuses();

            foreach ($data['statuses'] as $status) {
                $title = array_key_exists($status, $appointmentStatuses)
                    ? $appointmentStatuses[$status]['title']
                    : $status;
                $params['statuses'][] = [$status, $title];
            }
        }

        return $params;
    }

    public function saveEventData(int $id, SaveNewBookingEventRequest $request): void
    {
        $this->repository->updateDataById($id, [
            'statuses' => $request->statuses,
            'locale' => $request->locale,
            'called_from' => $request->calledFrom,
            'locations' => $request->locations,
            'services' => $request->services,
            'staffs' => $request->staffs,
            'categories' => $request->categories,
        ]);
    }
}
