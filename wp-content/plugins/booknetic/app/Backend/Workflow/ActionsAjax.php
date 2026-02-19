<?php

namespace BookneticApp\Backend\Workflow;

use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEventRegisterer;
use BookneticApp\Backend\Workflow\Repositories\WorkflowActionRepository;
use BookneticApp\Models\Staff;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Models\Workflow;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;

class ActionsAjax extends \BookneticApp\Providers\Core\Controller
{
    private $workflowEventsManager;
    private WorkflowActionRepository $workflowActionRepository;

    /**
     * @param WorkflowEventsManager $workflowEventsManager
     */
    public function __construct($workflowEventsManager)
    {
        $this->workflowEventsManager = $workflowEventsManager;
        $this->workflowActionRepository = new WorkflowActionRepository();
    }

    public function set_booking_status_view()
    {
        $id = Helper::_post('id', 0, 'int');

        $workflowActionInfo = $this->workflowActionRepository->get($id);

        if (! $workflowActionInfo) {
            return $this->response(false);
        }

        $data = json_decode($workflowActionInfo->data, true);

        $availableParams = $this->workflowEventsManager->get(Workflow::get($workflowActionInfo->workflow_id)['when'])
            ->getAvailableParams();

        $idShortcodes = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams, ['appointment_id']);

        $IDS = [];
        foreach ($idShortcodes as $shortcode) {
            $IDS[ '{'. $shortcode['code'] . '}' ] = [
                'name'      => $shortcode['name'],
                'selected'  => false
            ];
        }
        $selectedIds = isset($data['appointment_ids']) ? explode(',', $data['appointment_ids']) : [];
        foreach ($selectedIds as $ID) {
            if (empty($ID)) {
                continue;
            }

            if (array_key_exists($ID, $IDS)) {
                $IDS[$ID]['selected'] = true;
            } else {
                $IDS[$ID] = [
                    'selected' => true,
                    'name' => $ID
                ];
            }
        }

        return $this->modalView('action_set_booking_status', [
            'action_info'           => $workflowActionInfo,
            'appointment_ids'       => $IDS,
            'status'                => isset($data['status']) ? $data['status'] : '',
            'run_workflows'         => isset($data['run_workflows']) ? $data['run_workflows'] : true
        ], [
            'workflow_action_id' => $id,
        ]);
    }

    public function set_booking_status_save()
    {
        $id                 = Helper::_post('id', 0, 'int');
        $is_active          = Helper::_post('is_active', 1, 'int');
        $appointment_ids    = Helper::_post('appointment_ids', '', 'string');
        $status             = Helper::_post('status', '', 'string');
        $run_workflows      = Helper::_post('run_workflows', '', 'num');

        $checkWorkflowActionExist = $this->workflowActionRepository->get($id);

        if (! $checkWorkflowActionExist) {
            return $this->response(false);
        }

        $newData = [
            'appointment_ids' => $appointment_ids,
            'status' => $status,
            'run_workflows' => $run_workflows == 1
        ];

        $this->workflowActionRepository->update($id, [
            'data' => json_encode($newData),
            'is_active' => $is_active
        ]);

        return $this->response(true);
    }

    public function set_customer_category_view()
    {
        $id = Post::int('id');

        $workflowActionInfo = $this->workflowActionRepository->get($id);

        if (! $workflowActionInfo) {
            return $this->response(false);
        }

        $data = json_decode($workflowActionInfo->data, true);

        $customerCategories = CustomerCategory::query()->select(['id', 'name'])->fetchAll();

        return $this->modalView('set_customer_category_view', [
            'action_info' => $workflowActionInfo,
            'customerCategories' => $customerCategories,
            'category_id' => $data['category_id'] ?? null,
            'run_workflows' => $data['run_workflows'] ?? true
        ], [
            'workflow_action_id' => $id,
        ]);
    }

    public function set_customer_category_save()
    {
        $id                 = Post::int('id');
        $is_active          = Post::int('is_active', 1);
        $categoryId         = Post::int('category_id', 0);
        $run_workflows      = Post::int('run_workflows');

        $checkWorkflowActionExist = $this->workflowActionRepository->get($id);

        if (! $checkWorkflowActionExist) {
            return $this->response(false);
        }

        $newData = [
            'category_id' => $categoryId,
            'run_workflows' => $run_workflows === 1
        ];

        $this->workflowActionRepository->update($id, [
            'data' => json_encode($newData),
            'is_active' => $is_active
        ]);

        return $this->response(true);
    }

    public function in_app_notification_view()
    {
        $id = Post::int('id');
        $action = Post::string('event');

        $workflowActionInfo = $this->workflowActionRepository->get($id);

        if (! $workflowActionInfo) {
            return $this->response(false);
        }

        if (NotificationWorkflowEventRegisterer::getEventInstance($action) === null) {
            return $this->response(false, ['error_msg' => 'In App Notification driver not supported for this event']);
        }

        $availableParams = $this->workflowEventsManager->get(Workflow::get($workflowActionInfo->workflow_id)['when'])
            ->getAvailableParams();

        $subjectAndBodyShortcodes   = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams);
        $hasIdShortcodes = !empty($this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams, ['staff_id']));

        $data = json_decode($workflowActionInfo->data, true);

        return $this->modalView('in_app_notification_view', [
            'action_info' => $workflowActionInfo,
            'users' => $this->getStaffUserOptions($hasIdShortcodes),
            'to' => $this->resolveSelectedStaffIds($data['to'] ?? null),
            'all_shortcodes' => $subjectAndBodyShortcodes,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => $data['status'] ?? null,
            'run_workflows' => $data['run_workflows'] ?? true
        ], [
            'workflow_action_id' => $id,
        ]);
    }

    public function in_app_notification_save()
    {
        $id = Post::int('id');
        $to = Post::string('to');
        $title = Post::string('title');
        $message = Post::string('message');
        $status = Post::string('status');
        $is_active = Post::int('is_active');
        $run_workflows = Post::int('run_workflows');

        $checkWorkflowActionExist = $this->workflowActionRepository->get($id);

        if (! $checkWorkflowActionExist) {
            return $this->response(false);
        }

        $data = [
            'to' => $to,
            'title' => $title,
            'message' => $message,
            'status' => $status,
            'run_workflows' => $run_workflows === 1
        ];

        $this->workflowActionRepository->update($id, [
            'data' => json_encode($data),
            'is_active' => $is_active
        ]);

        return $this->response(true);
    }

    private function getStaffUserOptions(bool $hasIdShortcodes): array
    {
        $wpUsers = Staff::query()
            ->select(['user_id', 'name'])
            ->where('user_id', '<>', null)
            ->fetchAll();

        $users = [];

        if ($hasIdShortcodes) {
            $users[] = [
                'ID' => '{staff_user_id}',
                'user_login' => bkntc__('Staff of Appointment'),
            ];
        }

        foreach ($wpUsers as $wpUser) {
            $users[] = [
                'ID' => $wpUser->user_id,
                'user_login' => $wpUser->name,
            ];
        }

        return $users;
    }

    private function resolveSelectedStaffIds(?string $to): array
    {
        return isset($to) ? explode(',', $to) : [];
    }
}
