<?php

namespace BookneticApp\Backend\Customers\Helpers;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use WP_Error;

class CustomerService
{
    /**
     * @param CustomerData $customerData
     * @param bool $createWpUser
     * @return int
     */
    public static function createCustomer(CustomerData $customerData, bool $createWpUser): int
    {
        $newCustomerPass = null;
        $wpUserId = null;

        $wpUser = get_user_by('email', $customerData->email);

        if ($wpUser) {
            $wpUserId = $wpUser->ID;
            $wpUser->add_role('booknetic_customer');
        } elseif ($createWpUser) {
            $newCustomerPass = wp_generate_password(8, false);
            $wpUserId = self::createWpUser($customerData, $newCustomerPass);
        }

        $categoryId = CustomerCategory::query()->where('is_default', 1)->fetch()->id ?? null;

        Customer::query()->insert([
            'user_id'       => $wpUserId,
            'first_name'    => $customerData->first_name,
            'last_name'     => $customerData->last_name,
            'phone_number'  => $customerData->phone,
            'email'         => $customerData->email,
            'created_at'    => date('Y-m-d'),
            'category_id'   => $categoryId,
        ]);

        $customerId = DB::lastInsertedId();

        if ($customerId > 0 && $wpUserId > 0 && ! is_null($newCustomerPass)) {
            do_action('bkntc_customer_created', $customerId, $newCustomerPass);
        }

        return $customerId;
    }

    public static function createCustomerIfDoesntExist(CustomerData $customerData, bool $createWpUser)
    {
        $customerId = self::checkIfCustomerExists($customerData);

        if (empty($customerId)) {
            $customerId = self::createCustomer($customerData, $createWpUser);
        }

        return $customerId;
    }

    public static function checkIfCustomerExists(CustomerData $customerData)
    {
        $customerIdentifier = Helper::getOption('customer_identifier', 'email');

        if ($customerIdentifier === 'phone' && ! empty($customerData->phone)) {
            $checkCustomerExists = Customer::query()->where('phone_number', $customerData->phone)->fetch();
        } elseif ($customerIdentifier === 'email' && ! empty($customerData->email)) {
            $checkCustomerExists = Customer::query()->where('email', $customerData->email)->fetch();
        }

        return $checkCustomerExists->id ?? null;
    }

    public static function getCustomersOfLoggedInUser()
    {
        return Customer::query()
            ->where('user_id', Permission::userId())
            ->noTenant()
            ->fetchAll();
    }

    public static function updateOnlyEmptyDataOfCustomer($customerId, CustomerData $customerData): void
    {
        $customerInf = Customer::get($customerId);

        if (! $customerInf) {
            return;
        }

        $updateData = [];

        if (! empty($customerData->email) && empty($customerInf->email)) {
            $updateData['email'] = $customerData->email;
        }

        if (! empty($customerData->phone) && empty($customerInf->phone_number)) {
            $updateData['phone_number'] = $customerData->phone;
        }

        if (! empty($customerData->first_name) && empty($customerInf->first_name)) {
            $updateData['first_name'] = $customerData->first_name;
        }

        if (! empty($customerData->last_name) && empty($customerInf->last_name)) {
            $updateData['last_name'] = $customerData->last_name;
        }

        if (! empty($updateData)) {
            Customer::query()->where('id', $customerId)->update($updateData);
        }
    }

    public static function findCustomerTimezone($customerId)
    {
        $appointment = Appointment::query()->where('customer_id', $customerId)
            ->where('client_timezone', '<>', '-')
            ->select([ 'client_timezone' ])
            ->orderBy('id DESC')
            ->fetch();

        $timezone = $appointment->client_timezone ?? '-';

        return apply_filters('bkntc_customer_timezone', $timezone, $customerId);
    }

    public static function findCustomerLocale($customerId)
    {
        $appointment = Appointment::query()->where('customer_id', $customerId)
            ->where('locale', '<>', '')
            ->select([ 'locale' ])
            ->orderBy('id DESC')
            ->fetch();

        $locale = $appointment->locale ?? '-';

        return apply_filters('bkntc_customer_locale', $locale, $customerId);
    }

    /**
     * @param CustomerData $customerData
     * @param string $newCustomerPass
     * @return int|WP_Error|null
     */
    private static function createWpUser(CustomerData $customerData, string $newCustomerPass)
    {
        if (empty($customerData->email)) {
            return null;
        }

        if (get_user_by('email', $customerData->email) !== false) {
            return null;
        }

        $customerWPUserId = wp_insert_user([
            'user_login'	=>	$customerData->email,
            'user_email'	=>	$customerData->email,
            'display_name'	=>	$customerData->first_name . ' ' . $customerData->last_name,
            'first_name'	=>	$customerData->first_name,
            'last_name'		=>	$customerData->last_name,
            'role'			=>	'booknetic_customer',
            'user_pass'		=>	$newCustomerPass
        ]);

        /* If error thrown, it means there's already a WordPress user associated with this email */
        if (is_wp_error($customerWPUserId)) {
            $userInfo = get_user_by('email', $customerData->email);

            if ($userInfo && ! Helper::userHasAnyRole($userInfo, [ 'administrator', 'booknetic_customer', 'booknetic_staff', 'booknetic_saas_tenant' ])) {
                $userInfo->set_role('booknetic_customer');
            }

            $customerWPUserId = $userInfo->ID ?? null;
        } else {
            /* Save Customer phone number to WP User (WP stores the user's phone number in the billing data) */
            if (!empty($customerData->phone)) {
                add_user_meta($customerWPUserId, 'billing_phone', $customerData->phone, true);
            }
        }

        return $customerWPUserId;
    }
}
