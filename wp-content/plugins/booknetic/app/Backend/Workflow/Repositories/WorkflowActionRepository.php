<?php

namespace BookneticApp\Backend\Workflow\Repositories;

use BookneticApp\Models\WorkflowAction;
use BookneticApp\Providers\DB\Collection;

class WorkflowActionRepository
{
    /**
     * @param int $workflowId
     * @return WorkflowAction[]|Collection[]
     */
    public function getAllByWorkflowId(int $workflowId): array
    {
        return WorkflowAction::query()
            ->where('workflow_id', $workflowId)
            ->fetchAll();
    }

    /**
     * @param int $id
     * @return WorkflowAction|Collection|null
     */
    public function get(int $id): ?Collection
    {
        return WorkflowAction::query()->get($id);
    }

    public function create(array $data): int
    {
        WorkflowAction::query()->insert($data);

        return WorkflowAction::lastId();
    }

    public function update(int $id, array $data): void
    {
        WorkflowAction::query()
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): void
    {
        WorkflowAction::query()
            ->where('id', $id)
            ->delete();
    }
}
