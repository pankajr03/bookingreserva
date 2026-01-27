<?php

namespace BookneticApp\Backend\Mobile\Controllers;

use BookneticApp\Backend\Mobile\Exceptions\ApiException;
use BookneticApp\Backend\Mobile\Exceptions\InvalidSeatCountException;
use BookneticApp\Backend\Mobile\Services\SubscriptionService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Request\Post;

class SubscriptionAjaxController extends Controller
{
    private SubscriptionService $service;

    public function __construct(SubscriptionService $service)
    {
        $this->service = $service;
    }

    /**
     * @throws InvalidSeatCountException
     * @throws ApiException
     */
    public function subscribe()
    {
        $planId = Post::int('plan_id');
        $extraSeatCount = Post::int('additional_seat_count');

        $paymentLink = $this->service->createPaymentLink($planId, $extraSeatCount);

        return $this->response(true, ['payment_link' => $paymentLink]);
    }

    public function cancel()
    {
        Capabilities::must('mobile_app_cancel_subscription');

        $this->service->cancelSubscription();

        return $this->response(true);
    }

    /**
     * @return mixed|null
     */
    public function undoCancellation()
    {
        $this->service->undoCancellation();

        return $this->response(true);
    }
}
