<?php

namespace BookneticApp\Backend\Mobile\Controllers;

use BookneticApp\Backend\Mobile\Services\SubscriptionService;
use BookneticApp\Providers\Core\Controller;

class BillingAjaxController extends Controller
{
    private SubscriptionService $service;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->service = $subscriptionService;
    }

    public function info()
    {
        $billingInfo = $this->service->getActive();

        return $this->response(true, $billingInfo);
    }
}
