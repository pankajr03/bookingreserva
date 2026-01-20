<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Backend\Appointments\Helpers\Data\WorkflowFilterData;
use BookneticApp\Config;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Workflow;
use BookneticApp\Models\WorkflowAction;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;
use Exception;

final class ReminderService
{
    public function run(): void
    {
        set_time_limit(0);
        $this->triggerWorkflows();
    }

    private function triggerWorkflows(): void
    {
        $actions = [
            'booking_starts',
            'booking_ends',
            'customer_birthday'
        ];

        if (Helper::isSaaSVersion()) {
            $actions[] = 'tenant_notified';
        }

        $backUpTenantId = Permission::tenantId();
        $workflows = Workflow::noTenant()
            ->where('`when`', $actions)
            ->where('is_active', true)
            ->fetchAll();

        foreach ($workflows as $workflow) {
            Permission::setTenantId($workflow->tenant_id);

            $filter = $this->parseWorkflowFilters($workflow);
            $actions = $workflow->workflow_actions()
                ->where('is_active', true)
                ->fetchAll();

            switch ($workflow->when) {
                case 'tenant_notified':
                    $this->triggerTenantNotifiedWorkflow($workflow, $filter->getOffset());
                    break;
                case 'customer_birthday':
                    $this->triggerCustomerBirthdayWorkflow($actions, $filter);
                    break;
                default:
                    $this->triggerAppointmentWorkflows($workflow, $filter, $actions);
            }
        }

        Permission::setTenantId($backUpTenantId);
    }

    /**
     * @param WorkflowAction[]|Collection[] $actions
     * @param WorkflowFilterData $filter
     * @return void
     */
    private function triggerCustomerBirthdayWorkflow(array $actions, WorkflowFilterData $filter): void
    {
        $startDate = Date::dateTimeSQL($filter->getTime());
        $now = Date::dateTimeSQL();

        $startTime = Date::dateTimeSQL($startDate, '-1 minutes');
        $endTime = Date::dateTimeSQL($startDate, '+29 minutes');

        if (Date::epoch($now) < Date::epoch($startTime) || Date::epoch($now) > Date::epoch($endTime)) {
            return;
        }

        $offset = $filter->getOffset();

        $now = Date::dateTimeSQL($now, "+$offset seconds");

        $customerQuery = Customer::query()
            ->where(
                'Day(birthdate)',
                '=',
                (int)Date::format('d', $now)
            )
            ->where(
                'MONTH(birthdate)',
                '=',
                (int)Date::format('m', $now)
            );

        if ($filter->getGender() === 'male' || $filter->getGender() === 'female') {
            $customerQuery->where('gender', $filter->getGender());
        } elseif ($filter->getGender() === 'not_specified') {
            $customerQuery->where('IFNULL(gender,\'\')', "");
        }
        if (!empty($filter->getYears()) && !in_array('-', $filter->getYears(), true)) {
            $customerQuery->where('YEAR(birthdate)', 'in', $filter->getYears());
        }
        if (!empty($filter->getMonths()) && !in_array(0, $filter->getMonths(), true)) {
            $customerQuery->where('MONTH(birthdate)', 'in', $filter->getMonths());
        }

        $customers = $customerQuery->fetchAll();

        if (empty($customers)) {
            return;
        }

        foreach ($customers as $customer) {
            $hasWorkflowTriggered = Customer::getData(
                $customer->id,
                'triggered_customer_birthday_workflow_' . Date::format('Y'),
                false,
            );

            if ($hasWorkflowTriggered) {
                continue;
            }

            $params = [
                'customer_id' => $customer->id,
            ];

            foreach ($actions as $action) {
                $driver = Config::getWorkflowDriversManager()->get($action['driver']);
                if ($driver !== null) {
                    $driver->handle($params, $action, Config::getShortCodeService());
                }
            }

            Customer::setData(
                $customer->id,
                'triggered_customer_birthday_workflow_' . Date::format('Y'),
                true,
                false
            );
        }
    }

    /**
     * @param Workflow|Collection $workflow
     * @param int $offset
     * @return void
     */
    private function triggerTenantNotifiedWorkflow(Collection $workflow, int $offset): void
    {
        $tenants = Tenant::query()
            ->where('expires_in', '>=', date('Y-m-d', Date::epoch('now', '-5 minutes') + $offset))
            ->where('expires_in', '<=', date('Y-m-d', Date::epoch('now', '+5 minutes') + $offset))
            ->fetchAll();

        foreach ($tenants as $tenant) {
            $triggeredWorkflowIDs = json_decode(Tenant::getData($tenant->id, 'triggered_cronjob_workflows', '[]'), true);

            if (!in_array($workflow->id, $triggeredWorkflowIDs)) {
                do_action('bkntcsaas_tenant_notified', $tenant->id);

                $triggeredWorkflowIDs[] = $workflow->id;

                Tenant::setData($tenant->id, 'triggered_cronjob_workflows', json_encode($triggeredWorkflowIDs), count($triggeredWorkflowIDs));
            }
        }
    }

    /**
     * @param Workflow|Collection $workflow
     * @param WorkflowFilterData $filter
     * @param WorkflowAction[]|Collection[] $actions
     * @return void
     */
    private function triggerAppointmentWorkflows(Collection $workflow, WorkflowFilterData $filter, array $actions): void
    {
        $offset = $filter->getOffset();

        //                        if ($workflow->when === 'booking_ends') {
        //                    $nearbyAppointments = Appointment::where('ends_at', '>=', Date::epoch('now', '-5 minutes') + $offset)
        //                        ->where('ends_at', '<=', Date::epoch('now', '+5 minutes') + $offset);
        //                } else {
        //                    $nearbyAppointments = Appointment::where('starts_at', '>=', Date::epoch('now', '-5 minutes') + $offset)
        //                        ->where('starts_at', '<=', Date::epoch('now', '+5 minutes') + $offset);
        //                }

        if ($workflow->when === 'booking_ends') {
            $appointmentQuery = Appointment::query()
                ->where('ends_at', '>=', Date::epoch('now', '-5 minutes') + $offset)
                ->where('ends_at', '<=', Date::epoch('now', '+5 minutes') + $offset);
        } else {
            $appointmentQuery = Appointment::query()
                ->where('starts_at', '>=', Date::epoch('now', '-5 minutes') + $offset)
                ->where('starts_at', '<=', Date::epoch('now', '+5 minutes') + $offset);
        }

        if (count($filter->getLocations()) > 0) {
            $appointmentQuery->where('location_id', $filter->getLocations());
        }

        if (count($filter->getServices()) > 0) {
            $appointmentQuery->where('service_id', $filter->getServices());
        }

        if (count($filter->getStaffs()) > 0) {
            $appointmentQuery->where('staff_id', $filter->getStaffs());
        }

        if ($filter->getLocale() !== null) {
            $appointmentQuery->where('locale', $filter->getLocale());
        }

        if (count($filter->getStatuses()) > 0) {
            $appointmentQuery->where('status', $filter->getStatuses());
        }

        $appointments = $appointmentQuery->fetchAll();

        foreach ($appointments as $appointment) {
            $alreadyTriggeredWorkflowIDs = json_decode(Appointment::getData($appointment->id, 'triggered_cronjob_workflows', '[]'), true);
            if (in_array($workflow->id, $alreadyTriggeredWorkflowIDs)) {
                continue;
            }

            $params = [
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'staff_id' => $appointment->staff_id,
                'location_id' => $appointment->location_id,
                'service_id' => $appointment->service_id
            ];

            foreach ($actions as $action) {
                $driver = Config::getWorkflowDriversManager()->get($action['driver']);
                if ($driver !== null) {
                    $action->when = $workflow->when;
                    $driver->handle($params, $action, Config::getShortCodeService());
                }
            }

            $alreadyTriggeredWorkflowIDs[] = $workflow->id;

            Appointment::setData($appointment->id, 'triggered_cronjob_workflows', json_encode($alreadyTriggeredWorkflowIDs), count($alreadyTriggeredWorkflowIDs) > 1);
        }
    }

    /**
     * @param Workflow|Collection $workflow
     * @return WorkflowFilterData
     */
    private function parseWorkflowFilters(Collection $workflow): WorkflowFilterData
    {
        $filter = new WorkflowFilterData();

        try {
            if (!empty($workflow['data'])) {
                $data = json_decode($workflow['data'], true);
                $offset = $data['offset_value'] * 60;
                $offset *= $data['offset_sign'] === 'before' ? 1 : -1;

                if ($data['offset_type'] === 'hour') {
                    $offset *= 60;
                }
                if ($data['offset_type'] === 'day') {
                    $offset *= 60 * 24;
                }

                $filter->setOffset($offset)
                    ->setStatuses($data['statuses'] ?? [])
                    ->setLocations($data['locations'] ?? [])
                    ->setServices($data['services'] ?? [])
                    ->setStaffs($data['staffs'] ?? [])
                    ->setGender($data['gender'] ?? null)
                    ->setYears($data['years'] ?? [])
                    ->setMonths($data['month'] ?? [])
                    ->setTime($data['input_time'] ?? '')
                    ->setLocale($data['locale'] ?: null);
            }
        } catch (Exception $e) {
        }

        return $filter;
    }
}
