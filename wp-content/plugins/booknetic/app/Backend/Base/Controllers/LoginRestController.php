<?php

namespace BookneticApp\Backend\Base\Controllers;

use BookneticApp\Backend\Base\Services\LoginService;
use BookneticApp\Providers\Core\RestRequest;

class LoginRestController
{
    private LoginService $service;

    public function __construct()
    {
        $this->service = new LoginService();
    }

    /**
     * @throws \Exception
     */
    public function login(RestRequest $request): array
    {
        $deviceInfo = $request->param('deviceInfo', [], RestRequest::TYPE_ARRAY);

        if (empty($deviceInfo)) {
            throw new \RuntimeException(bkntc__('Device info must be array'), 422);
        }

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $response = $this->service->login($username, $deviceInfo);

        return [$response];
    }
}
