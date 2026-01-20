<?php

namespace BookneticSaaS\Providers\FSCode;

use BookneticSaaS\Providers\Helpers\Helper;

class FSCodeAPIClientLite
{
    public const API_URL = 'https://api.fs-code.com/v3/';
    private ?string $proxy = null;

    private function getDefaultHeaders(): array
    {
        return [
            'X-Website: ' . site_url(),
            'X-Product-Version: ' . Helper::getVersion(),
            'X-PHP-Version: ' . PHP_VERSION,
            'X-Wordpress-Version: ' . get_bloginfo('version'),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    public function request(string $endpoint, string $method = 'GET', array $data = []): array
    {
        $url = static::API_URL . ltrim($endpoint, '/');

        $ch = curl_init();

        $headers = $this->getDefaultHeaders();
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        if ($this->proxy) {
            $options[CURLOPT_PROXY] = $this->proxy;
        }

        $method = strtoupper($method);
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if (!empty($data)) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
        } elseif ($method === 'GET' && !empty($data)) {
            $query = http_build_query($data);
            $options[CURLOPT_URL] .= '?' . $query;
        }

        curl_setopt_array($ch, $options);

        try {
            $rawResponse = curl_exec($ch);

            if ($rawResponse === false) {
                throw new \RuntimeException('cURL error: ' . curl_error($ch));
            }

            $decoded = json_decode($rawResponse, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $decoded = [];
        } finally {
            curl_close($ch);
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function setProxy($proxy): self
    {
        $this->proxy = $proxy;

        return $this;
    }
}
