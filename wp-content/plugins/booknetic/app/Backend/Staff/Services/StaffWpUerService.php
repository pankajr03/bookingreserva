<?php

namespace BookneticApp\Backend\Staff\Services;

use BookneticApp\Backend\Staff\Exceptions\StaffValidationException;
use BookneticApp\Backend\Staff\DTOs\Request\StaffRequest;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\DB\Collection;
use WP_User;

class StaffWpUerService
{
    /**
     * Handle WordPress user login linking for staff.
     *
     * @param StaffRequest $dto
     * @param Staff|Collection|null $oldStaff
     *
     * @return int|null
     * @throws StaffValidationException
     */
    public function handle(StaffRequest $dto, ?Collection $oldStaff = null): ?int
    {
        if (!Permission::isAdministrator() && !Capabilities::userCan('staff_allow_to_login')) {
            return $oldStaff->user_id ?? null;
        }

        if (!$dto->allowToLogin) {
            return 0;
        }

        if ($dto->useExistingWpUser === 'yes') {
            return $this->useExistingUser($dto, $oldStaff);
        }

        if (empty($dto->wpUserPassword) && (!$dto->isEdit() || ($oldStaff !== null && empty($oldStaff->user_id)))) {
            throw new StaffValidationException(bkntc__('Please type the password of the WordPress user!'));
        }

        return $this->createOrUpdateUser($dto);
    }

    /**
     * @param StaffRequest $dto
     * @param Staff|Collection|null $oldStaff
     *
     * @return int
     * @throws StaffValidationException
     */
    private function useExistingUser(StaffRequest $dto, ?Collection $oldStaff = null): int
    {
        if ($dto->wpUser <= 0) {
            throw new StaffValidationException(bkntc__('Please select a WordPress user!'));
        }

        if ($oldStaff && $oldStaff->user_id && $oldStaff->user_id !== $dto->wpUser) {
            $old = new WP_User($oldStaff->user_id);
            $old->remove_role('booknetic_staff');
        }

        $user = new WP_User($dto->wpUser);
        $user->add_role('booknetic_staff');

        if ($dto->updateWpUser && $dto->isEdit()) {
            wp_update_user([
                'ID'           => $dto->wpUser,
                'user_email'   => $dto->email,
                'display_name' => $dto->name,
                'first_name'   => $dto->name,
            ]);
        }

        return $dto->wpUser;
    }

    /**
     * @param StaffRequest $dto
     *
     * @return int
     * @throws StaffValidationException
     */
    private function createOrUpdateUser(StaffRequest $dto): int
    {
        $email = $dto->email;
        $exists = get_user_by('email', $email);

        if ($exists) {
            $user = new WP_User($exists->ID);
            $user->add_role('booknetic_staff');

            if (!empty($dto->wpUserPassword)) {
                wp_update_user(['ID' => $exists->ID, 'user_pass' => $dto->wpUserPassword]);
            }

            return $exists->ID;
        }

        $newUserId = wp_insert_user([
            'user_login'   => $email,
            'user_email'   => $email,
            'display_name' => $dto->name,
            'first_name'   => $dto->name,
            'role'         => 'booknetic_staff',
            'user_pass'    => $dto->wpUserPassword,
        ]);

        if (is_wp_error($newUserId)) {
            throw new StaffValidationException($newUserId->get_error_message());
        }

        return $newUserId;
    }
}
