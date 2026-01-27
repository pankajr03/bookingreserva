<?php

namespace BookneticApp\Backend\Base\Services;

use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\IoC\Container;
use RuntimeException;

class LoginService
{
    private $apiClient;

    /**
     * @throws \ReflectionException
     */
    public function __construct()
    {
        $this->apiClient = Container::get(FSCodeAPIClient::class);
    }

    /**
     * @param string $username
     * @param array $deviceInfo
     * @return array
     */
    public function login(string $username, array $deviceInfo): array
    {
        $response = $this->apiClient->requestNew('mobile-app/admin/seats/login', 'POST', ['username' => $username, 'device_data' => $deviceInfo], 'v1');

        if (!$response->getStatus()) {
            throw new RuntimeException($response->getErrorMessage(), $response->getCode());
        }

        return $response->getData();
    }
}
