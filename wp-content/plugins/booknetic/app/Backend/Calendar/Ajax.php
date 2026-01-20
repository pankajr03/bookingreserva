<?php

namespace BookneticApp\Backend\Calendar;

use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Config;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\DB\QueryBuilder;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    public function get_calendar()
    {
        Capabilities::must('calendar');

        $postData = $this->getCalendarPostData();

        DB::enableCache();

        $appointmentQuery = $this->getAppointmentFilterQuery($postData);

        $filterQuery = clone $appointmentQuery;

        $appointments = $appointmentQuery->leftJoin('staff', ['name', 'profile_image'])
            ->leftJoin('location', ['name'])
            ->leftJoin('service', [ 'name', 'color', 'max_capacity' ])
            ->leftJoin('customer', ['first_name', 'last_name'])
            ->fetchAll();

        $response = $this->getCalendarEventData($appointments, $filterQuery, $postData);

        DB::disableCache();

        return $this->response(true, $response);
    }

    /**
     * @throws CapabilitiesException
     */
    public function getCalendarForMonth()
    {
        Capabilities::must('calendar');

        $postData = $this->getCalendarPostData();

        DB::enableCache();

        $appointmentQuery = $this->getAppointmentFilterQuery($postData);

        $filterQuery = clone $appointmentQuery;

        $appointmentsData = $appointmentQuery
            ->leftJoin('staff', ['name', 'profile_image'])
            ->leftJoin('location', ['name'])
            ->leftJoin('service', ['name', 'color', 'max_capacity'])
            ->leftJoin('customer', ['first_name', 'last_name'])
            ->orderBy('starts_at')
            ->fetchAll();

        $appointments = [];
        foreach ($appointmentsData as $appointment) {
            $date = Date::format('Y-m-d', $appointment['starts_at']);

            if (isset($appointments[$date])) {
                $appointments[$date]['appointment_count'] += 1;
                continue;
            }

            $appointment['appointment_date'] = $date;
            $appointment['appointment_count'] = 1;

            $appointments[$date] = $appointment;
        }

        $response = $this->getCalendarEventData($appointments, $filterQuery, $postData);

        DB::disableCache();

        return $this->response(true, $response);
    }

    /**
     * @throws \Exception
     */
    public function reschedule_appointment()
    {
        $dateTimeISO = Helper::_post('new_date_time', '', 'string');
        $appointmentID = Helper::_post('appointment_id', 0, 'int');
        $triggerWorkflows = Helper::_post('trigger_workflows', 0, 'int');
        $staffId = Helper::_post('staff_id', false, 'int');

        if (empty($appointmentID)) {
            return $this->response(false);
        }

        if (! Date::isValid($dateTimeISO)) {
            return $this->response(false, bkntc__('Incorrect Date and Time'));
        }

        AppointmentService::reschedule($appointmentID, $dateTimeISO, $dateTimeISO, boolval($triggerWorkflows), false, $staffId);

        return $this->response(true);
    }

    private static function getContrastColor($hexcolor)
    {
        Capabilities::must('calendar');

        $r = hexdec(substr($hexcolor, 1, 2));
        $g = hexdec(substr($hexcolor, 3, 2));
        $b = hexdec(substr($hexcolor, 5, 2));
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return ($yiq >= 185) ? '#292D32' : '#FFF';
    }

    /**
     * @return array{startTime: int, endTime: int, staffFilter: array, locationFilter: array, servicesFilter: array, statusesFilter: array, paymentFilter: array}
     */
    private function getCalendarPostData(): array
    {
        $startTime		= Post::string('start');
        $endTime		= Post::string('end');

        return  [
            'startTime'	        =>	Date::epoch($startTime),
            'endTime'		    =>	Date::epoch($endTime),
            'staffFilter'	    =>	$this->getValidatedArray(Post::array('staff')),
            'locationFilter'	=>	$this->getValidatedArray(Post::array('locations')),
            'servicesFilter'	=>	$this->getValidatedArray(Post::array('services')),
            'statusesFilter'	=>	$this->getValidatedArray(Post::array('statuses')),
            'paymentFilter'	    =>	$this->getValidatedArray(Post::array('payments'))
        ];
    }

    /**
     * @param array $postData
     * @return QueryBuilder
     */
    private function getAppointmentFilterQuery(array $postData): QueryBuilder
    {
        // De Morgan's law
        // not ( a OR b ) = not(a) AND not(b)
        // not ( starts_at > $endTime OR ends_at < $startTime )
        // starts_at <= $endTime AND ends_at >= $startTime
        $appointmentQuery = Appointment::query()
            ->where('starts_at', '<=', $postData['endTime'])
            ->where('ends_at', '>=', $postData['startTime']);

        if (! empty($postData['staffFilter'])) {
            $appointmentQuery = $appointmentQuery->where('staff_id', 'in', $postData['staffFilter']);
        }

        if (! empty($postData['locationFilter'])) {
            $appointmentQuery = $appointmentQuery->where('location_id', $postData['locationFilter']);
        }

        if (! empty($postData['servicesFilter'])) {
            $appointmentQuery = $appointmentQuery->where('service_id', $postData['servicesFilter']);
        }

        if (! empty($postData['statusesFilter'])) {
            $appointmentQuery = $appointmentQuery->where('status', $postData['statusesFilter']);
        }
        if (! empty($postData['paymentFilter'])) {
            $appointmentQuery = $appointmentQuery->where('payment_status', $postData['paymentFilter']);
        }

        return $appointmentQuery;
    }

    /**
     * @param array $postData
     * @return array
     */
    private function getValidatedArray(array $postData): array
    {
        $filteredArray = [];

        foreach ($postData as $dataId) {
            if (is_numeric($dataId) && $dataId > 0) {
                $filteredArray[] = (int)$dataId;
            }
        }

        return $filteredArray;
    }

    private function getCalendarEventData(array $appointments, QueryBuilder $filterQuery, array $postData): array
    {
        $events = [];

        $appointmentCardColor = Helper::getOption('calendar_event_color', 'eventColor');
        $appointmentCardContent = Helper::getOption('calendar_event_content', "");
        $useCustomContent = Helper::getOption('use_custom_calendar_card_content', false);

        foreach ($appointments as $appointment) {
            $eventData = [
                'appointment_id'	   =>	$appointment['id'],
                'customer_id'          =>   $appointment['customer_id'],
                'location_id'          =>   $appointment['location_id'],
                'service_id'           =>   $appointment['service_id'],
                'staff_id'             =>   $appointment['staff_id'],
            ];
            $cardContent = $useCustomContent ? Config::getShortCodeService()->replace($appointmentCardContent, $eventData) : $appointmentCardContent;

            $color = empty($appointment['service_color']) ? '#ff7675' : $appointment['service_color'];

            if ($appointmentCardColor === 'statusColor') {
                $color = Helper::appointmentStatus($appointment['status'])['color'];
            }

            $events[] = apply_filters('bkntc_filter_calendar_event_object', [
                'appointment_id'		=>	$appointment['id'],
                'title'					=>	htmlspecialchars($appointment['service_name']),
                'event_title'			=>	'',
                'event_content'         =>  $cardContent,
                'color'					=>	$color,
                'text_color'			=>	static::getContrastColor($color),
                'location_name'			=>	htmlspecialchars($appointment['location_name']),
                'service_name'			=>	htmlspecialchars($appointment['service_name']),
                'staff_name'			=>	htmlspecialchars($appointment['staff_name']),
                'staff_id'			    =>	$appointment['staff_id'] ,
                'resourceId'			=>	$appointment['staff_id'] ,
                'staff_profile_image'	=>	Helper::profileImage($appointment['staff_profile_image'], 'Staff'),
                'start_time'			=>	Date::time($appointment['starts_at']),
                'end_time'				=>	Date::time($appointment['ends_at']),
                'start'					=>	Date::format('Y-m-d\TH:i:s', $appointment['starts_at']),
                'end'                   =>  Date::format('Y-m-d\TH:i:s', $appointment['ends_at']),
                'duration'              =>  Date::epoch($appointment['ends_at']) - Date::epoch($appointment['starts_at']),
                'customer'				=>	$appointment['customer_first_name'] . ' ' . $appointment['customer_last_name'],
                'customers_count'		=>	1,
                'status'				=>	Helper::appointmentStatus($appointment['status']),
                'total_count'           => $appointment['appointment_count'] ?? 1
            ], $appointment, $filterQuery);
        }

        $events = apply_filters('bkntc_calendar_events', $events, $postData['startTime'], $postData['endTime'], $postData['staffFilter']);
        $businessHours  = Timesheet::query()
            ->where('service_id', 'is', null)
            ->where('staff_id', 'is', null)
            ->fetch();

        return [
           'data'	=>	$events,
           'businessHours' => $businessHours,
           'appointmentCardContent' => $appointmentCardContent,
           'appointmentCardColor'   => $appointmentCardColor,
           'enableCustomCalendarCardContent' => $useCustomContent,
       ];
    }
}
