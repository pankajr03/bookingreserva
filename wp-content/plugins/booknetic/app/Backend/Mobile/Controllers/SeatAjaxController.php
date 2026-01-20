<?php

namespace BookneticApp\Backend\Mobile\Controllers;

use BookneticApp\Backend\Mobile\Exceptions\AppPasswordCreatingException;
use BookneticApp\Backend\Mobile\Exceptions\UserNotFoundException;
use BookneticApp\Backend\Mobile\Services\SubscriptionService;
use BookneticApp\Backend\Mobile\Services\SeatService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Request\Post;

class SeatAjaxController extends Controller
{
    private SeatService $seatService;

    private SubscriptionService $subscriptionService;

    public function __construct(
        SeatService $seatService,
        SubscriptionService $subscriptionService
    ) {
        $this->seatService = $seatService;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * @return array
     * @throws CapabilitiesException
     */
    public function manage_seats_modal(): array
    {
        Capabilities::must('mobile_app_manage_additional_seats');

        $response = $this->subscriptionService->getActive();

        return $this->response(true, ['result' => $response]);
    }

    public function manage_seats()
    {
        Capabilities::must('mobile_app_manage_seats');

        $seatCount = Post::int('additional_seat_count');

        if ($seatCount <= 0) {
            return $this->response(false);
        }

        $this->subscriptionService->update($seatCount);

        return $this->response(true);
    }

    public function update_seat_preview()
    {
        Capabilities::must('mobile_app_preview_proration');
        $seatCount = Post::int('additional_seat_count');

        if ($seatCount < 0) {
            return $this->response(false, 'Invalid seat count');
        }

        $response = $this->subscriptionService->getPreview($seatCount);

        return $this->response(true, ['result' => $response->getData()]);
    }

    /**
     * @throws AppPasswordCreatingException
     * @throws UserNotFoundException
     */
    public function assign()
    {
        $id = Post::int('id');

        $response = $this->seatService->assign($id);

        return $this->response(true, [
            'data' => $response
        ]);
    }

    public function hasAvailableSeat()
    {
        try {
            $this->seatService->hasAvailable();
        } catch (\Exception $e) {
            return $this->response(true, [
                'status_response' => false,
                'message' => $e->getMessage()
            ]);
        }

        return $this->response(true, [
            'status' => true
        ]);
    }

    public function assign_user_modal()
    {
        $q = Post::string('q');
        $wpUsers = $this->seatService->getWpUsers($q);

        return $this->response(true, ['results' => $wpUsers]);
    }

    /**
     * @throws AppPasswordCreatingException
     * @throws UserNotFoundException
     */
    public function regenerate_password()
    {
        $username = Post::string('username');
        $seatId = Post::int('seatId');

        $appPassword = $this->seatService->regenerateAppPassword($username, $seatId);

        return $this->response(true, [
            'app_password' => $appPassword,
            'username' => $username
        ]);
    }

    /**
     * @throws CapabilitiesException
     */
    public function logout()
    {
        Capabilities::must('mobile_app_logout');

        $seatId = Post::int('seatId');

        $this->seatService->logout($seatId);

        return $this->response(true);
    }

    public function unassign()
    {
        Capabilities::must('mobile_app_unassign_seat');

        $seatId = Post::int('seatId');

        if (!($seatId > 0)) {
            return $this->response(false);
        }

        $this->seatService->unassign($seatId);

        return $this->response(true);
    }
}
