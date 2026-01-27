<?php

namespace BookneticApp\Backend\Workflow\Services\WorkflowEventServices;

use BookneticApp\Backend\Workflow\DTOs\Request\SaveTenantNotifiedEventRequest;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\Abstracts\BaseEventService;

class TenantNotifiedEventService extends BaseEventService
{
    public function getEventParams(int $id): array
    {
        $data = $this->repository->getWorkflowData($id);

        return [
            'offset_value' => $data['offset_value'] ?? 0,
            'offset_type' => $data['offset_type'] ?? 'minute',
        ];
    }

    public function saveEventData(int $id, SaveTenantNotifiedEventRequest $request): void
    {
        $this->repository->updateDataById($id, [
            'offset_sign' => 'before',
            'offset_value' => $request->offsetValue,
            'offset_type' => $request->offsetType
        ]);
    }
}
