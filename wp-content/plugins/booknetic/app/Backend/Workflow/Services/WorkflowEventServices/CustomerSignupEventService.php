<?php

namespace BookneticApp\Backend\Workflow\Services\WorkflowEventServices;

use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\Abstracts\BaseEventService;

class CustomerSignupEventService extends BaseEventService
{
    public function getEventParams(int $id): array
    {
        $data = $this->repository->getWorkflowData($id);
        $selectionData = $this->getCommonSelectionData($data);

        return [
            'locale' => $data['locale'] ?? get_locale(),
            'locales' => $this->getLocales(),
            'categories' => $selectionData['categories'] ?? [],
        ];
    }

    public function saveEventData(int $id, string $locale, array $categories): void
    {
        $this->repository->updateDataById($id, [
            'locale' => $locale,
            'categories' => $categories,
        ]);
    }
}
