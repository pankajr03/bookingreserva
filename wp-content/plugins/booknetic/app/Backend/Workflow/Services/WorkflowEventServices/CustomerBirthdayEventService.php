<?php

namespace BookneticApp\Backend\Workflow\Services\WorkflowEventServices;

use BookneticApp\Backend\Workflow\DTOs\Request\SaveCustomerBirthdayEventRequest;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\Abstracts\BaseEventService;
use RuntimeException;

class CustomerBirthdayEventService extends BaseEventService
{
    public function getEventParams(int $id): array
    {
        $workflow = $this->repository->get($id);

        if ($workflow === null) {
            throw new RuntimeException(bkntc__('Workflow not found'));
        }

        $data = $this->repository->getWorkflowData($id);
        $selectionData = $this->getCommonSelectionData($data);

        return [
            'offset_sign' => $data['offset_sign'] ?? 'before',
            'offset_value' => $data['offset_value'] ?? 0,
            'offset_type' => $data['offset_type'] ?? 'day',
            'gender' => $data['gender'] ?? '',
            'years' => $data['years'] ?? [],
            'month' => $data['month'] ?? [],
            'selected_time' => $data['input_time'] ?? '',
            'categories' => $selectionData['categories'] ?? [],
        ];
    }

    public function saveEventData(int $id, SaveCustomerBirthdayEventRequest $request): void
    {
        $this->repository->updateDataById($id, [
            'month' => $request->months,
            'years' => $request->years,
            'gender' => $request->gender,
            'offset_sign' => $request->offsetSign,
            'offset_value' => $request->offsetValue,
            'offset_type' => 'day',
            'input_time' => $request->inputTime,
            'categories' => $request->categories
        ]);
    }
}
