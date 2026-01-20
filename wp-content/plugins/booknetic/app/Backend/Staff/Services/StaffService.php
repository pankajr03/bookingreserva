<?php

namespace BookneticApp\Backend\Staff\Services;

use BookneticApp\Backend\Base\Repository\TimesheetRepository;
use BookneticApp\Backend\Staff\DTOs\Request\StaffRequest;
use BookneticApp\Backend\Staff\DTOs\Response\StaffGetResponse;
use BookneticApp\Backend\Staff\DTOs\Response\StaffResponse;
use BookneticApp\Backend\Staff\DTOs\Response\StaffWpUserSelectOptionResponse;
use BookneticApp\Backend\Staff\Mappers\StaffMapper;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Backend\Staff\Exceptions\{
    StaffLimitExceededException,
    StaffNotFoundException,
    StaffPermissionException,
    StaffValidationException
};
use BookneticApp\Backend\Staff\Repository\StaffRepository;
use BookneticApp\Backend\Staff\Repository\ServiceStaffRepository;
use BookneticApp\Models\{Appointment, Data, Holiday, ServiceStaff, SpecialDay, Staff, Timesheet};
use BookneticApp\Providers\Core\{Capabilities, CapabilitiesException, Permission};
use BookneticApp\Providers\Helpers\{Date, Helper};

/**
 * Service layer for managing Staff entities.
 *
 * Handles validation, capability checks, database operations via repository,
 * and relation management (timesheet, holidays, etc.).
 */
class StaffService
{
    private StaffRepository $repository;
    private ServiceStaffRepository $serviceStaffRepository;

    private TimesheetRepository $timesheetRepository;

    private StaffRelationService  $relationService;

    private StaffWpUerService  $staffLoginService;
    public function __construct()
    {
        $this->repository = new StaffRepository();
        $this->serviceStaffRepository = new ServiceStaffRepository();
        $this->timesheetRepository = new TimesheetRepository();
        $this->relationService = new StaffRelationService();
        $this->staffLoginService = new StaffWpUerService();
    }

    /**
     * Creates or updates a staff record.
     *
     * @param StaffRequest $dto
     * @return array
     * @throws CapabilitiesException
     * @throws StaffValidationException
     * @throws StaffNotFoundException
     * @throws StaffLimitExceededException
     */
    public function save(StaffRequest $dto): array
    {
        $this->validate($dto);
        $this->checkCapabilities($dto);

        if ($dto->isEdit()) {
            $this->update($dto);
        } else {
            $this->create($dto);
        }

        $this->saveRelations($dto);

        $this->repository->handleTranslation($dto->id, $dto->translations);

        return [
            'message' => bkntc__('Staff saved successfully.'),
            'id'      => $dto->id,
        ];
    }

    /**
     * Validates basic staff data before saving.
     *
     * @param StaffRequest $dto
     * @throws StaffValidationException
     */
    private function validate(StaffRequest $dto): void
    {
        if (empty($dto->name) || empty($dto->email)) {
            throw new StaffValidationException(
                bkntc__('Please fill in all required fields correctly!')
            );
        }
        if (empty($dto->locations) || count(array_filter($dto->locations)) === 0) {
            throw new StaffValidationException(bkntc__('Please select at least one location.'));
        }
    }

    /**
     * Checks capability and plan limit before save.
     *
     * @param StaffRequest $dto
     * @throws CapabilitiesException
     * @throws StaffLimitExceededException
     */
    private function checkCapabilities(StaffRequest $dto): void
    {
        if ($dto->isEdit()) {
            Capabilities::must('staff_edit');
        } else {
            Capabilities::must('staff_add');

            $allowedLimit = Capabilities::getLimit('staff_allowed_max_number');
            if ($allowedLimit > -1 && Staff::query()->count() >= $allowedLimit) {
                throw new StaffLimitExceededException($allowedLimit);
            }
        }
    }

    /**
     * Creates a new staff member.
     *
     * @param StaffRequest $dto
     * @throws StaffValidationException
     */
    private function create(StaffRequest $dto): void
    {
        $userId = $this->staffLoginService->handle($dto);
        if ($dto->image) {
            $image = $this->handleProfileImage($dto->image);
        }

        $data = [
            'name'          => $dto->name,
            'user_id'       => $userId,
            'email'         => $dto->email,
            'phone_number'  => $dto->phone,
            'about'         => $dto->note,
            'profile_image' => $image ?? null,
            'locations'     => implode(',', $dto->locations ?? []),
            'profession'    => $dto->profession,
            'is_active'     => 1,
        ];

        $dto->id = $this->repository->insert($data);

        do_action('bkntc_staff_created', $dto->id);
    }

    /**
     * Updates an existing staff member.
     *
     * @param StaffRequest $dto
     * @throws StaffValidationException|StaffNotFoundException
     */
    private function update(StaffRequest $dto): void
    {
        $staff = $this->repository->get($dto->id);
        if (!$staff) {
            throw new StaffNotFoundException($dto->id);
        }
        $uploadedPath = null;
        if ($dto->image) {
            $uploadedPath = $this->handleProfileImage($dto->image);
        }

        $userId = (new StaffWpUerService())->handle($dto, $staff);

        $data = [
            'name'          => $dto->name,
            'user_id'       => $userId,
            'email'         => $dto->email,
            'phone_number'  => $dto->phone,
            'about'         => $dto->note,
            'profile_image' => $uploadedPath ?? $staff->profile_image,
            'locations'     => implode(',', $dto->locations ?? []),
            'profession'    => $dto->profession,
        ];

        $this->repository->update($dto->id, $data);

        $this->timesheetRepository->deleteByStaffId($dto->id);
    }

    /**
     * @throws StaffNotFoundException
     * @throws StaffValidationException
     * @throws StaffPermissionException
     */
    public function delete(array $ids, bool $deleteWpUser = true): array
    {
        if (! (Permission::isAdministrator() || Capabilities::userCan('staff_delete'))) {
            throw new StaffPermissionException(
                bkntc__('You do not have sufficient permissions to perform this action.')
            );
        }

        $allowWpDelete = $deleteWpUser
            && (Permission::isAdministrator() || Capabilities::userCan('staff_delete_wordpress_account'));

        $deletedIds = [];

        foreach ($ids as $id) {
            $staff = $this->repository->get($id);
            if (!$staff) {
                throw new StaffNotFoundException($id);
            }

            $hasAppointments = Appointment::where('staff_id', $id)->count() > 0;
            if ($hasAppointments) {
                throw new StaffValidationException(
                    bkntc__('This staff has active appointments. Please remove them first!')
                );
            }

            if (!empty($staff->user_id)) {
                $this->handleWordPressUserDeletion((int)$staff->user_id, $allowWpDelete);
            }

            $this->deleteStaffRelations($id);

            $this->deleteProfileImage($staff->profile_image ?? null);

            $this->repository->delete($id);

            $deletedIds[] = $id;
        }

        return [
            'message'     => bkntc__('Staff successfully deleted.'),
            'deleted_ids' => $deletedIds,
        ];
    }

    /**
     * Deletes staff-related records from all dependent tables.
     *
     * @param int $staffId
     */
    private function deleteStaffRelations(int $staffId): void
    {
        ServiceStaff::where('staff_id', $staffId)->delete();
        Holiday::where('staff_id', $staffId)->delete();
        SpecialDay::where('staff_id', $staffId)->delete();
        Timesheet::where('staff_id', $staffId)->delete();
        Data::where('table_name', 'staff')->where('row_id', $staffId)->delete();
    }

    /**
     * Handles WordPress user deletion or role removal.
     *
     * @param int $wpUserId
     * @param bool $deleteCompletely
     */
    private function handleWordPressUserDeletion(int $wpUserId, bool $deleteCompletely): void
    {
        $userData = get_userdata($wpUserId);
        if (!$userData || !in_array('booknetic_staff', (array)$userData->roles, true)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/user.php';

        if ($deleteCompletely) {
            wp_delete_user($wpUserId);
        } else {
            $userData->remove_role('booknetic_staff');
        }
    }

    /**
     * Deletes staff profile image from filesystem if exists.
     *
     * @param string|null $imageName
     */
    private function deleteProfileImage(?string $imageName): void
    {
        if (empty($imageName)) {
            return;
        }

        $filePath = Helper::uploadedFile($imageName, 'Staff');
        if (is_file($filePath) && is_writable($filePath)) {
            wp_delete_file($filePath);
        }
    }

    /**
     * Saves all related entities (timesheet, holidays, services, etc.)
     *
     * @param StaffRequest $dto
     */
    private function saveRelations(StaffRequest $dto): void
    {
        $this->relationService->saveAll($dto);
    }

    /**
     * Checks if the current user can create a new staff member.
     *
     * @throws StaffPermissionException
     */
    public function ensureUserCanCreate(): void
    {
        if (!Permission::isAdministrator() && !Capabilities::userCan('staff_add')) {
            throw new StaffPermissionException(
                bkntc__('You do not have sufficient permissions to perform this action.')
            );
        }
    }

    /**
     * Ensures that staff creation does not exceed plan limit.
     *
     * @throws StaffLimitExceededException
     */
    public function checkAllowedStaffLimit(): void
    {
        $allowedLimit = Capabilities::getLimit('staff_allowed_max_number');
        if ($allowedLimit > -1 && Staff::count() >= $allowedLimit) {
            throw new StaffLimitExceededException($allowedLimit);
        }
    }

    /**
     * Toggles the visibility (active/inactive) of a staff member.
     *
     * @param int $staffId
     * @return array
     * @throws StaffNotFoundException
     */
    public function toggleVisibility(int $staffId): array
    {
        $staff = $this->repository->get($staffId);
        if (!$staff) {
            throw new StaffNotFoundException($staffId);
        }

        $newStatus = $staff->is_active ? 0 : 1;

        $this->repository->update($staffId, ['is_active' => $newStatus]);

        return [
            'staff_id'   => $staffId,
            'new_status' => $newStatus,
        ];
    }

    /**
     * Generates a list of available time slots for dropdowns and autocomplete fields.
     *
     * @param string $search
     * @return array
     */
    public function getAvailableTimes(string $search = ''): array
    {
        $timeslotLength = Helper::getOption('timeslot_length', 5);
        $tEnd = Date::epoch('00:00:00', '+1 days');
        $timeCursor = Date::epoch('00:00:00');
        $data = [];

        while ($timeCursor <= $tEnd) {
            $timeId = Date::timeSQL($timeCursor);
            $timeText = Date::time($timeCursor);

            if ($timeCursor == $tEnd) {
                $timeText = '24:00';
                $timeId   = '24:00';
            }

            $timeCursor += $timeslotLength * 60;

            if (! empty($search) && strpos($timeText, $search) === false) {
                continue;
            }

            $data[] = [
                'id'   => $timeId,
                'text' => $timeText
            ];
        }

        return $data;
    }
    private function handleProfileImage(?array $image): ?string
    {
        if (!$image || empty($image['tmp_name'])) {
            return null;
        }

        $pathInfo  = pathinfo($image['name']);
        $extension = strtolower($pathInfo['extension'] ?? '');

        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            throw new \RuntimeException(bkntc__('Only JPG and PNG images are allowed!'));
        }

        $fileName = md5(base64_encode(rand(1, 9999999) . microtime(true))) . '.' . $extension;
        $target   = Helper::uploadedFile($fileName, 'Staff');

        move_uploaded_file($image['tmp_name'], $target);

        return $fileName;
    }

    public function get(int $id = 0): StaffGetResponse
    {
        $staff     = $id ? $this->repository->get($id) : null;
        $selectedServices = $id ? $this->serviceStaffRepository->getIdsByStaffId($id) : [];
        $timesheet     = $this->getTimesheet($id);

        $specialDays = $this->relationService->getSpecialDays($id);
        $holidaysArr = $this->relationService->getHolidays($id);
        $locations = $this->relationService->getLocations();
        $services = $this->relationService->getServices();

        $users       = $this->getWordPressUsers();
        $defaultCountryCode = Helper::getOption('default_phone_country_code', '');

        $mapper = new StaffMapper();

        $response = new StaffGetResponse();
        $response->setId($id);

        if ($staff !== null) {
            $staffResponse = $mapper->toResponse($staff);
            $response->setStaff($staffResponse);
        } else {
            $response->setStaff(new StaffResponse());
        }

        $response->setSelectedServices($selectedServices);
        $response->setTimesheet($timesheet['schedule']);
        $response->setHasSpecificTimesheet($timesheet['hasSpecific']);
        $response->setSpecialDays($mapper->toSpecialDayListResponse($specialDays));
        $response->setHolidays(json_encode($holidaysArr));
        $response->setLocations($mapper->toSelectOptionResponseList($locations));
        $response->setServices($mapper->toSelectOptionResponseList($services));
        $response->setUsers($users);
        $response->setDefaultCountryCode($defaultCountryCode);

        return $response;
    }

    private function getTimesheet(int $staffId): array
    {
        $row = DB::DB()->get_row(
            DB::DB()->prepare(
                'SELECT staff_id, timesheet FROM ' . DB::table('timesheet') . ' 
             WHERE ((service_id IS NULL AND staff_id IS NULL) OR (staff_id=%d)) 
             ' . DB::tenantFilter() . ' ORDER BY staff_id DESC LIMIT 1',
                [$staffId]
            ),
            ARRAY_A
        );

        if (empty($row['timesheet'])) {
            $default = array_fill(0, 7, [
                'day_off' => 0, 'start' => '00:00', 'end' => '24:00', 'breaks' => [],
            ]);

            return ['schedule' => $default, 'hasSpecific' => false];
        }

        return [
            'schedule'    => json_decode($row['timesheet'], true) ?: [],
            'hasSpecific' => $row['staff_id'] > 0,
        ];
    }

    /**
     * @return array<StaffWpUserSelectOptionResponse>
     */
    private function getWordPressUsers(): array
    {
        return array_map(static fn ($user) => new StaffWpUserSelectOptionResponse(
            $user->ID,
            $user->display_name,
            $user->user_email
        ), get_users([
            'fields' => ['ID', 'display_name', 'user_email'],
        ]));
    }

    public function getStaffList(string $search = '', int $location = 0, int $service = 0): array
    {
        return $this->repository->getAll($search, $location, $service);
    }
}
