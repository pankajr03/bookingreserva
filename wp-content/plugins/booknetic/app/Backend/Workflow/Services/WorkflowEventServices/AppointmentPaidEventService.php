<?php

namespace BookneticApp\Backend\Workflow\Services\WorkflowEventServices;

use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\Abstracts\BaseEventService;

class AppointmentPaidEventService extends BaseEventService
{
    public function getEventParams(int $id): array
    {
        $data = $this->repository->getWorkflowData($id);

        return [
            'locale' => $data['locale'] ?? get_locale(),
            'locales' => $this->getLocales()
        ];
    }

    public function saveEventData(int $id, string $locale): void
    {
        $this->repository->updateDataById($id, [
            'locale' => $locale
        ]);
    }
}
