<?php

namespace BookneticApp\Providers\Core;

use Exception;
use WP_REST_Request;

class RestRequest
{
    private WP_REST_Request $request;
    private array $params;
    private array $body;

    public const TYPE_INTEGER = 'integer';
    public const TYPE_STRING  = 'string';
    public const TYPE_ARRAY   = 'array';
    public const TYPE_FLOAT   = 'float';
    public const TYPE_BOOL    = 'bool';
    public const TYPE_EMAIL   = 'email';

    public function __construct(WP_REST_Request $request)
    {
        $this->request = $request;
        $this->body    = $request->get_json_params() ?? [];
        $this->params  = $request->get_query_params() ?? [];
    }

    public function param($key, $default = null, $dataType = null, $whitelist = [])
    {
        return $this->checkTypeAndGet($key, $default, $dataType, $whitelist);
    }

    /**
     * @throws Exception
     */
    public function require($key, $checkType, $errorMessage = [], $whitelist = [])
    {
        $value = $this->param($key, '', $checkType, $whitelist);

        if (empty($value)) {
            throw new Exception($errorMessage);
        }

        return $value;
    }

    public function getRequest(): WP_REST_Request
    {
        return $this->request;
    }

    private function checkTypeAndGet($key, $default = null, $dataType = null, $whitelist = [])
    {
        $res = $this->request->get_param($key) ?? $default;

        if (!empty($dataType)) {
            if ($dataType === self::TYPE_BOOL) {
                $res = is_bool($res) ? $res : $default;
            } elseif ($dataType === self::TYPE_INTEGER) {
                $res = is_numeric($res) ? (int)$res : $default;
            } elseif ($dataType === self::TYPE_STRING) {
                $res = is_string($res) ? $res : $default;
            } elseif ($dataType === self::TYPE_ARRAY) {
                $res = is_array($res) ? $res : $default;
            } elseif ($dataType === self::TYPE_FLOAT) {
                $res = is_numeric($res) ? (float)$res : $default;
            } elseif ($dataType === self::TYPE_EMAIL) {
                if (!is_string($res) || filter_var($res, FILTER_VALIDATE_EMAIL) === false) {
                    return $default;
                }
            }
        }

        if (!empty($whitelist) && !in_array($res, $whitelist) && $dataType !== self::TYPE_ARRAY) {
            $res = $default;
        } elseif (!empty($whitelist) && $dataType === self::TYPE_ARRAY) {
            $res = array_intersect($whitelist, $res);
            $res = array_values($res);
        }

        return $res;
    }
}
