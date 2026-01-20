<?php

namespace BookneticApp\Backend\Mobile\Controllers;

use BookneticApp\Backend\Mobile\Services\SubscriptionService;
use BookneticApp\Backend\Mobile\Services\PlanService;
use BookneticApp\Backend\Mobile\Services\SeatService;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Helper;

class MobileAppController extends Controller
{
    private SeatService $seatService;

    private PlanService $planService;

    private SubscriptionService $subscriptionService;

    public function __construct(
        SeatService $seatService,
        PlanService $planService,
        SubscriptionService $subscriptionService
    ) {
        $this->seatService = $seatService;
        $this->planService = $planService;
        $this->subscriptionService = $subscriptionService;
    }

    public function index(): void
    {
        $view = Helper::_get('view', 'manage_users', 'string', [
            'manage_users', 'billing', 'settings'
        ]);

        if (!method_exists($this, $view)) {
            $this->response(false, bkntc__('View not found'));
            exit;
        }

        $this->$view();
    }

    public function manage_users(): void
    {
        $response = $this->seatService->getAll();

        $this->loadView('manage_users', [
            'availableSeats' => $response['availableSeats'],
            'users' => $response['users'],
        ]);
    }

    public function billing(): void
    {
        $subscription = $this->subscriptionService->getActive();

        $plans = $this->planService->getAll();

        $this->loadView('billing', [
            'plans' => $plans,
            'subscription' => $subscription
        ]);
    }

    public function settings(): void
    {
        $allowStaffToRegenerateAppPassword = Helper::getOption('mobile_app_allow_staff_to_regenerate_app_password');

        $this->loadView('settings', [
            'allow_staff_to_regenerate_app_password' => $allowStaffToRegenerateAppPassword
        ]);
    }

    private function loadView(string $view, array $data = []): void
    {
        $html = Helper::renderView('Mobile.Controllers.view.' . $view, $data);

        $subscription = $this->subscriptionService->getActive();
        $plans = $this->planService->getAll();

        if (empty($subscription)) {
            $this->view('plan', [
                'billingInfo' => $subscription,
                "plans" => $plans
            ]);

            return;
        }

        $this->view('index', [
            'html' => $html,
            'info' => $subscription,
            'currentView' => $view,
        ]);
    }
}
