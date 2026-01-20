<?php

namespace BookneticApp\Backend\Staff\Controllers;

use BookneticApp\Backend\Staff\DTOs\Request\StaffRequest;
use BookneticApp\Backend\Staff\Exceptions\StaffLimitExceededException;
use BookneticApp\Backend\Staff\Exceptions\StaffNotFoundException;
use BookneticApp\Backend\Staff\Exceptions\StaffPermissionException;
use BookneticApp\Backend\Staff\Exceptions\StaffValidationException;
use BookneticApp\Backend\Staff\Services\StaffService;
use BookneticApp\Providers\UI\TabUI;
use BookneticApp\Providers\Core\{Capabilities, CapabilitiesException};
use BookneticApp\Providers\Request\Post;

class StaffAjaxController extends \BookneticApp\Providers\Core\Controller
{
    private StaffService $staffService;

    public function __construct()
    {
        $this->staffService = new StaffService();
    }

    /**
     * @return mixed|null
     * @throws StaffPermissionException
     * @throws StaffLimitExceededException
     */
    public function add_new()
    {
        $this->staffService->ensureUserCanCreate();
        $this->staffService->checkAllowedStaffLimit();

        $response = $this->staffService->get();
        $this->registerStaffTabs();

        return $this->modalView('add_new', $response);
    }

    public function edit()
    {
        $staffId = Post::int('id');
        $response = $this->staffService->get($staffId);

        $this->registerStaffTabs();

        return $this->modalView('add_new', $response);
    }

    public function registerStaffTabs(): void
    {
        TabUI::get('staff_add')
            ->item('details')->setTitle(bkntc__('STAFF DETAILS'))
            ->addView(__DIR__ . '/../Controllers/view/tab/details.php', [], 1)
            ->setPriority(1);

        TabUI::get('staff_add')
            ->item('timesheet')->setTitle(bkntc__('WEEKLY SCHEDULE'))
            ->addView(__DIR__ . '/../Controllers/view/tab/timesheet.php', [], 1)
            ->setPriority(2);

        TabUI::get('staff_add')
            ->item('special_days')->setTitle(bkntc__('SPECIAL DAYS'))
            ->addView(__DIR__ . '/../Controllers/view/tab/special_days.php', [], 1)
            ->setPriority(3);

        TabUI::get('staff_add')
            ->item('holidays')->setTitle(bkntc__('HOLIDAYS'))
            ->addView(__DIR__ . '/../Controllers/view/tab/holidays.php', [], 1)
            ->setPriority(4);
    }

    /**
     * Handle create or update staff in a single endpoint.
     *
     * @throws StaffValidationException
     * @throws StaffNotFoundException
     * @throws CapabilitiesException
     * @throws StaffLimitExceededException
     */
    public function save()
    {
        $dto = $this->makeStaffRequest();
        $result = $this->staffService->save($dto);
        do_action('bkntc_after_request_staff_save_staff', ['staff_id' => $result['id'], 'is_edit' => $dto->isEdit()]);

        return $this->response(true, $result);
    }

    /**
     * @return mixed|null
     * @throws StaffNotFoundException
     * @throws CapabilitiesException
     */
    public function hide()
    {
        Capabilities::must('staff_edit');

        $staffId = Post::int('staff_id');
        $result = $this->staffService->toggleVisibility($staffId);

        return $this->response(true, $result);
    }

    public function get_available_times_all()
    {
        $search = Post::string('q', '');
        $results = $this->staffService->getAvailableTimes($search);

        return $this->response(true, ['results' => $results]);
    }
    public function makeStaffRequest(): StaffRequest
    {
        $image = isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])
            ? $_FILES['image']
            : null;

        return new StaffRequest([
            'id'                => Post::int('id'),
            'wpUser'            => Post::int('wp_user'),
            'name'              => Post::string('name'),
            'email'             => Post::email('email'),
            'allowToLogin'      => (bool) Post::int('allow_staff_to_login', 0, [0, 1]),
            'useExistingWpUser' => Post::string('wp_user_use_existing', 'yes', ['yes', 'no']),
            'wpUserPassword'    => Post::string('wp_user_password'),
            'updateWpUser'      => (bool) Post::int('update_wp_user', 0, [0, 1]),
            'profession'        => Post::string('profession'),
            'phone'             => Post::string('phone'),
            'note'              => Post::string('note'),
            'locations'         => explode(',', Post::string('locations', '')),
            'services'          => explode(',', Post::string('services', '')),
            'weeklySchedule'    => json_decode(Post::string('weekly_schedule', '[]'), true),
            'specialDays'       => json_decode(Post::string('special_days', '[]'), true),
            'holidays'          => json_decode(Post::string('holidays', '[]'), true),
            'translations'      => json_decode(Post::string('translations', '[]'), true),
            'image'             => $image,
        ]);
    }
}
