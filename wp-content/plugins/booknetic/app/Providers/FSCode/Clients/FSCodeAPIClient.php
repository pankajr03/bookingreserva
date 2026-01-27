<?php

namespace BookneticApp\Providers\FSCode\Clients;

use BookneticApp\Providers\FSCode\Clients\RequestDTOs\DTOs\Response\ApiResponse;
use BookneticVendor\GuzzleHttp\Client;
use BookneticVendor\GuzzleHttp\Exception\ClientException;
use BookneticVendor\GuzzleHttp\Exception\GuzzleException;
use BookneticVendor\GuzzleHttp\Exception\ServerException;
use RuntimeException;

class FSCodeAPIClient
{
    private const API_URL = 'https://api.fs-code.com/';

    private FSCodeAPIClientContextDto $context;

    public function __construct(FSCodeAPIClientContextDto $context)
    {
        $this->context = $context;
    }

    private function getClient(): Client
    {
        return new Client([
            'verify' => false,
            'headers' => [
                'X-License-Code'      => $this->context->licenseCode,
                'X-Website'           => $this->context->website,
                'X-Product-Version'   => $this->context->productVersion,
                'X-PHP-Version'       => $this->context->phpVersion,
                'X-Wordpress-Version' => $this->context->wordpressVersion,
                'Content-type'        => 'application/json',
                'Accept'              => 'application/json',
            ],
        ]);
    }

    /**
     * @deprecated
     * @param $endpoint
     * @param $method
     * @param $data
     * @return void
     */
    public function request($endpoint, $method = 'GET', $data = []): array
    {
        try {
            $response = $this->requestNew($endpoint, $method, $data);
        } catch (\Exception $_) {
            return [];
        }

        return $response->getData();
    }

    /**
     * @param $endpoint
     * @param $method
     * @param $data
     * @param $version
     * @return ApiResponse
     */
    public function requestNew($endpoint, $method = 'GET', $data = [], $version = 'v3'): ApiResponse
    {
        $url = static::API_URL . $version . '/' . $endpoint;

        $options = [];

        if ($method === 'POST' && ! empty($data)) {
            $options['json'] = $data;
        } elseif ($method === 'PATCH' && ! empty($data)) {
            $options['json'] = $data;
        } elseif ($method === 'GET' && ! empty($data)) {
            $options['query'] = $data;
        }

        $apiResponse = new ApiResponse();

        try {
            $response = $this->getClient()->request($method, $url, $options);
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true) ?? [];

            $apiResponse->setStatus(true);
            $apiResponse->setData($data);
            $apiResponse->setCode($response->getStatusCode());
        } catch (ClientException $e) {
            $response = $e->getResponse();

            $body = (string) $response->getBody();
            $parsed = json_decode($body, true);

            $apiResponse->setErrorMessage($parsed['error']['message'] ?? $body);
            $apiResponse->setCode($response->getStatusCode());
        } catch (ServerException $e) {
            $response = $e->getResponse();

            $apiResponse->setErrorMessage(bkntc__('Something went wrong.'));
            $apiResponse->setCode($response->getStatusCode());
        } catch (\Exception|GuzzleException $e) {
            $apiResponse->setErrorMessage(bkntc__('Something went wrong.'));
        }

        if (!$apiResponse->getStatus()) {
            throw new RuntimeException($apiResponse->getErrorMessage(), $apiResponse->getCode());
        }

        return $apiResponse;
    }

    public static function uploadFileFromName(string $name, string $dst): void
    {
        $url = sprintf('%s/%s', self::API_URL, $name);

        self::uploadFileFromUrl($url, $dst);
    }

    public static function uploadFileFromUrl(string $src, string $dst): void
    {
        $img = file_get_contents($src);

        file_put_contents($dst, $img);
    }

    public function setLicense($license_code): void
    {
        $this->context->licenseCode = $license_code;
    }
}
