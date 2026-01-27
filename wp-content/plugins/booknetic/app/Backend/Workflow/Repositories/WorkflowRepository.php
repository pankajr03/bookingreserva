<?php

namespace BookneticApp\Backend\Workflow\Repositories;

use BookneticApp\Models\Workflow;
use BookneticApp\Providers\DB\Collection;

class WorkflowRepository
{
    /**
     * @param int $id
     * @return Workflow|Collection|null
     */
    public function get(int $id): ?Collection
    {
        return Workflow::query()->get($id);
    }

    public function create(array $data): int
    {
        Workflow::query()->insert($data);

        return Workflow::lastId();
    }

    public function update(int $id, array $data): void
    {
        Workflow::query()
            ->where('id', $id)
            ->update($data);
    }

    public function getWorkflowData(int $id): array
    {
        $workflow = $this->get($id);

        if ($workflow === null) {
            return [];
        }

        if (empty($workflow['data'])) {
            return [];
        }

        return json_decode($workflow['data'], true) ?? [];
    }

    public function updateDataById(int $id, array $data): void
    {
        Workflow::query()
            ->where('id', $id)
            ->update([
                'data' => json_encode($data)
            ]);
    }
}
