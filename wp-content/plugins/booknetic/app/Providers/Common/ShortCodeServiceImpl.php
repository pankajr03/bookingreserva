<?php

namespace BookneticApp\Providers\Common;

use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class ShortCodeServiceImpl
{
    public static function replace($text, $data)
    {
        if (!empty($data['appointment_id'])) {
            $appointmentSO = AppointmentSmartObject::load($data['appointment_id']);
            $appointment = $appointmentSO->getInfo();
            $appointmentSO->addLocaleFilter();

            $extraServices = AppointmentExtra::query()
                ->select([
                        AppointmentExtra::getField('quantity'),
                        AppointmentExtra::getField('price')
                    ])
                ->leftJoin('extra', ['name'])
                ->where('appointment_id', $appointment->id)
                ->fetchAll();

            $serviceExtraList = [];
            foreach ($extraServices as $extra) {
                $serviceExtraList[] = $extra->extra_name . ($extra->quantity > 1 ? ' x' . $extra->quantity : '') . ' - ' . Helper::price($extra->price * $extra->quantity);
            }
            $serviceExtraList = implode(' , <br/>', $serviceExtraList);

            $addToGoogleCalendarURL = 'https://www.google.com/calendar/render?action=TEMPLATE&text='
                . urlencode($appointmentSO->getServiceInf()->name)
                . '&dates=' . (Date::UTCDateTime($appointment->starts_at, 'Ymd\THis\Z') . '/'
                    . Date::UTCDateTime($appointment->ends_at, 'Ymd\THis\Z'))
                . '&details=&location=' . urlencode($appointmentSO->getLocationInf()->name) . '&sprop=&sprop=name:';

            $appointmentStatus = Helper::appointmentStatus($appointment->status);

            if (strpos($text, '{total_appointments_in_group}') !== false) {
                $totalAppointmentsGroup = Appointment::query()
                    ->where('location_id', $appointment->location_id)
                    ->where('service_id', $appointment->service_id)
                    ->where('staff_id', $appointment->staff_id)
                    ->where('starts_at', $appointment->starts_at)
                    ->where('status', 'in', Helper::getBusyAppointmentStatuses())
                    ->where(
                        fn ($query) => $query->where('payment_method', 'local')
                            ->orWhere('payment_status', 'paid')
                    )
                    ->leftJoin('customer', ['email', 'first_name', 'last_name'])->count();

                $text = str_replace('{total_appointments_in_group}', $totalAppointmentsGroup, $text);
            }

            $startDate = Date::datee($appointment->starts_at);
            $startDateTime = Date::dateTime($appointment->starts_at);

            $startDateClientTz = Date::datee($appointment->starts_at, false, true, $appointment->client_timezone);
            $startDateTimeClientTz = Date::dateTime($appointment->starts_at, false, true, $appointment->client_timezone);

            $arr = [
                '{appointment_id}' => $appointment->id,

                '{appointment_date}' => $startDate,
                '{appointment_start_date}' => $startDate,
                '{appointment_end_date}' => Date::datee($appointment->ends_at),
                '{appointment_date_time}' => $startDateTime,
                '{appointment_start_date_time}' => $startDateTime,
                '{appointment_end_date_time}' => Date::dateTime($appointment->ends_at),
                '{appointment_start_time}' => Date::time($appointment->starts_at),
                '{appointment_end_time}' => Date::time($appointment->ends_at),
                '{recurring_appointments_date}' => $appointmentSO->getRecurringDateAndTimes(),
                '{recurring_appointments_date_time}' => $appointmentSO->getRecurringDateAndTimes(true),

                '{appointment_date_client}' => $startDateClientTz,
                '{appointment_start_date_client}' => $startDateClientTz,
                '{appointment_end_date_client}' => Date::datee($appointment->ends_at, false, true, $appointment->client_timezone),
                '{appointment_date_time_client}' => $startDateTimeClientTz,
                '{appointment_start_date_time_client}' => $startDateTimeClientTz,
                '{appointment_end_date_time_client}' => Date::dateTime($appointment->ends_at, false, true, $appointment->client_timezone),
                '{appointment_start_time_client}' => Date::time($appointment->starts_at, false, true, $appointment->client_timezone),
                '{appointment_end_time_client}' => Date::time($appointment->ends_at, false, true, $appointment->client_timezone),
                '{recurring_appointments_date_client}' => $appointmentSO->getRecurringDateAndTimes(false, false, true, $appointment->client_timezone),
                '{recurring_appointments_date_time_client}' => $appointmentSO->getRecurringDateAndTimes(true, false, true, $appointment->client_timezone),

                '{appointment_duration}' => Helper::secFormat($appointment->ends_at - $appointment->starts_at),
                '{appointment_buffer_before}' => Helper::secFormat($appointment->starts_at - $appointment->busy_from),
                '{appointment_buffer_after}' => Helper::secFormat($appointment->busy_to - $appointment->ends_at),
                '{appointment_status}' => $appointment->status_name,
                '{appointment_status_icon_code}' => $appointmentStatus['icon'],
                '{appointment_status_icon_color}' => $appointmentStatus['color'],
                '{appointment_service_price}' => Helper::price($appointmentSO->getPrice('service_price')->price),
                '{appointment_extras_price}' => Helper::price($appointmentSO->getPrice('service_extra')->price),
                '{appointment_extras_list}' => $serviceExtraList,
                '{appointment_discount_price}' => Helper::price($appointmentSO->getPrice('discount')->price),
                '{appointment_sum_price}' => Helper::price($appointmentSO->getTotalAmount()),
                '{appointments_total_price}' => Helper::price($appointmentSO->getTotalAmount(true)),
                '{appointment_paid_price}' => Helper::price($appointmentSO->getRealPaidAmount()),
                '{appointment_payment_method}' => Helper::paymentMethod($appointmentSO->getInfo()->payment_method),

                '{appointment_created_date}' => Date::datee($appointment->created_at),
                '{appointment_created_time}' => Date::time($appointment->created_at),
                '{appointment_created_date_client}' => Date::datee($appointment->created_at),
                '{appointment_created_time_client}' => Date::time($appointment->created_at),

                '{add_to_google_calendar_link}' => $addToGoogleCalendarURL,

                '{appointment_brought_people}' => $appointment->weight - 1,
                '{appointment_total_attendees}' => $appointment->weight,

                '{appointment_notes}' => $appointment->note,
            ];

            $text = str_replace(array_keys($arr), array_values($arr), $text);
        }

        if (!empty($data['service_id'])) {
            $service = Service::query()
                ->leftJoin('category', ['name'])
                ->withTranslations()
                ->get($data['service_id']);

            $arr = [
                '{service_name}' => $service->name,
                '{service_price}' => Helper::price($service->price),
                '{service_duration}' => Helper::secFormat($service->duration * 60),
                '{service_notes}' => $service->notes,
                '{service_color}' => $service->color,
                '{service_image_url}' => Helper::profileImage($service->image, 'Services'),
                '{service_max_capacity}' => $service->max_capacity ?? 1,
                '{service_category_name}' => $service->category_name,
            ];

            $text = str_replace(array_keys($arr), array_values($arr), $text);
        }

        if (!empty($data['staff_id'])) {
            $staffInf = Staff::query()->get($data['staff_id']);

            $arr = [
                '{staff_name}' => $staffInf->name,
                '{staff_email}' => $staffInf->email,
                '{staff_phone}' => $staffInf->phone_number,
                '{staff_about}' => $staffInf->about,
                '{staff_profile_image_url}' => Helper::profileImage($staffInf->profile_image, 'Staff')
            ];

            $text = str_replace(array_keys($arr), array_values($arr), $text);
        }

        if (!empty($data['customer_id'])) {
            $customerInf = Customer::noTenant()
                ->leftJoin('category', ['name'])
                ->get($data['customer_id']);

            $arr = [
                '{customer_full_name}' => $customerInf->full_name,
                '{customer_first_name}' => $customerInf->first_name,
                '{customer_last_name}' => $customerInf->last_name,
                '{customer_phone}' => $customerInf->phone_number,
                '{customer_email}' => $customerInf->email,
                '{customer_birthday}' => $customerInf->birthdate,
                '{customer_notes}' => $customerInf->notes,
                '{customer_category}' => $customerInf->category_name,
                '{customer_profile_image_url}' => Helper::profileImage($customerInf->profile_image, 'Customers')
            ];

            $text = str_replace(array_keys($arr), array_values($arr), $text);
        }

        if (!empty($data['customer_password'])) {
            $text = str_replace('{customer_password}', $data['customer_password'], $text);
        }

        if (!empty($data['location_id'])) {
            $locationInf = Location::query()
                ->withTranslations()
                ->get($data['location_id']);

            $arr = [
                '{location_name}' => $locationInf->name,
                '{location_address}' => $locationInf->address,
                '{location_image_url}' => Helper::profileImage($locationInf->image, 'Locations'),
                '{location_phone_number}' => $locationInf->phone_number,
                '{location_notes}' => $locationInf->notes,
                '{location_google_maps_url}' => 'https://maps.google.com/?q=' . $locationInf->latitude . ',' . $locationInf->longitude
            ];

            $text = str_replace(array_keys($arr), array_values($arr), $text);
        }

        $arr = [
            '{company_name}' => Helper::getOption('company_name', ''),
            '{company_image_url}' => Helper::profileImage(Helper::getOption('company_image', ''), 'Settings'),
            '{company_website}' => Helper::getOption('company_website', ''),
            '{company_phone}' => Helper::getOption('company_phone', ''),
            '{company_address}' => Helper::getOption('company_address', ''),
            '{sign_in_page}' => get_permalink(Helper::getOption('regular_sing_in_page', '', false)),
            '{sign_up_page}' => get_permalink(Helper::getOption('regular_sign_up_page', '', false))
        ];

        return str_replace(array_keys($arr), array_values($arr), $text);
    }

    public static function replacePaymentLink($text, $data)
    {
        if (empty($data['appointment_id'])) {
            return $text;
        }

        $paymentRegex = '/({appointment_payment_link_)(\w+)}/';
        preg_match_all($paymentRegex, $text, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $text;
        }

        $paymentGatewaySlugs = [];

        foreach ($matches as $match) {
            $paymentGatewaySlugs[] = $match[2];
        }

        $totalAmountQuery = AppointmentPrice::query()
            ->where('appointment_id', DB::field(Appointment::getField('id')))
            ->select('sum(price * negative_or_positive)', true);

        $appointments = Appointment::query()
            ->leftJoin('customer', ['first_name', 'last_name', 'email', 'profile_image', 'phone_number'])
            ->leftJoin('staff', ['name', 'profile_image'])
            ->leftJoin('location', ['name'])
            ->leftJoin('service', ['name'])
            ->where(Appointment::getField('id'), $data['appointment_id'])
            ->selectSubQuery($totalAmountQuery, 'total_price');

        $appointment = $appointments->fetch();

        if ($appointment === null) {
            return $text;
        }

        $serviceCustomMethods = Service::getData($appointment->service_id, 'custom_payment_methods');
        $serviceCustomMethods = json_decode($serviceCustomMethods, true);
        if (empty($serviceCustomMethods)) {
            $paymentMethods = PaymentGatewayService::getEnabledGatewayNames();
        } else {
            $paymentMethods = $serviceCustomMethods;
        }

        if ($appointment->total_price == $appointment->paid_amount) {
            $paymentMethods = [];
        }

        foreach ($paymentMethods as $paymentMethod) {
            if (!in_array($paymentMethod, $paymentGatewaySlugs)) {
                continue;
            }

            $paymentGatewayService = PaymentGatewayService::find($paymentMethod);

            if (!property_exists($paymentGatewayService, 'createPaymentLink')) {
                continue;
            }

            try {
                $paymentLink = $paymentGatewayService->createPaymentLink([$appointment]);
            } catch (\Exception $exception) {
                continue;
            }

            if (isset($paymentLink->data['url']) && !empty($paymentLink->data['url'])) {
                $text = str_replace("{appointment_payment_link_$paymentMethod}", $paymentLink->data['url'], $text);
            }
        }

        return $text;
    }
}
