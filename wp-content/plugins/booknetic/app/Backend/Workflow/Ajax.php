<?php

namespace BookneticApp\Backend\Workflow;

use BookneticApp\Backend\Workflow\Repositories\WorkflowActionRepository;
use BookneticApp\Backend\Workflow\Repositories\WorkflowRepository;
use BookneticApp\Providers\Common\WorkflowDriversManager;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Request\Post;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    private WorkflowDriversManager $workflowDriversManager;

    private WorkflowEventsManager $workflowEventsManager;

    private WorkflowRepository $workflowRepository;

    private WorkflowActionRepository $workflowActionRepository;

    public function __construct(WorkflowEventsManager $workflowEventsManager)
    {
        $this->workflowEventsManager = $workflowEventsManager;
        $this->workflowDriversManager = $workflowEventsManager->getDriverManager();
        $this->workflowRepository = new WorkflowRepository();
        $this->workflowActionRepository = new WorkflowActionRepository();
    }

    /**
     * @throws CapabilitiesException
     */
    public function add_new()
    {
        Capabilities::must('workflow_add');

        $drivers = $this->workflowDriversManager->getList();
        $events = $this->workflowEventsManager->getAll();

        return $this->modalView('add_new', [
            'drivers' => $drivers,
            'events' => $events
        ]);
    }

    public function add_new_action()
    {
        $event = Post::string('event');
        $drivers = $this->workflowDriversManager->getList();

        return $this->modalView('add_new_action', [
            'drivers' => $drivers,
            'event' => $event
        ]);
    }

    public function create_workflow()
    {
        $name = Post::string('workflow_name');
        $event = Post::string('when');
        $action = Post::string('do_this');
        $isActive = Post::int('is_active');

        $workflowDriver = $this->workflowDriversManager->get($action);

        if ($workflowDriver === null) {
            return $this->response(false);
        }

        if (!array_key_exists($event, $this->workflowEventsManager->getAll())) {
            return $this->response(false);
        }

        $workflowId = $this->workflowRepository->create([
            'name' => $name,
            'when' => $event,
            'is_active' => $isActive
        ]);

        $sqlDataWorkflowAction = [
            'workflow_id' => $workflowId,
            'driver' => $action,
            'is_active' => 1
        ];

        $this->workflowActionRepository->create($sqlDataWorkflowAction);

        return $this->response(true, [
            'workflow_id' => $workflowId
        ]);
    }

    public function create_new_action()
    {
        $actionDriver = Post::string('action_driver');
        $workflowId = Post::int('workflow_id');

        if ($workflowId <= 0) {
            return $this->response(false);
        }

        $workflowDriver = $this->workflowDriversManager->get($actionDriver);

        if ($workflowDriver === null) {
            return $this->response(false);
        }

        $actionId = $this->workflowActionRepository->create([
            'driver' => $actionDriver,
            'workflow_id' => $workflowId,
            'is_active' => 1
        ]);
        $driverEditAction = $workflowDriver->getEditAction();

        return $this->response(true, [
            'action_id' => $actionId,
            'edit_action' => $driverEditAction
        ]);
    }

    public function get_action_list_view()
    {
        $workflowId = Post::int('workflow_id');

        if ($workflowId <= 0) {
            return '';
        }

        $workflowActions = $this->workflowActionRepository->getAllByWorkflowId($workflowId);
        $workflowInfo = $this->workflowRepository->get($workflowId);

        return $this->modalView('action_list_view', [
            'workflow_info' => $workflowInfo,
            'actions' => $workflowActions,
            'events_manager' => $this->workflowEventsManager
        ]);
    }

    public function delete_action()
    {
        $id = Post::int('id');

        if ($id <= 0) {
            return $this->response(false);
        }

        $this->workflowActionRepository->delete($id);

        return $this->response(true);
    }

    public function save_workflow()
    {
        $id = Post::int('id', -1);
        $name = Post::string('name');
        $isActive = Post::int('is_active', 1);

        $this->workflowRepository->update($id, [
            'name' => $name,
            'is_active' => $isActive
        ]);

        return $this->response(true);
    }
}
