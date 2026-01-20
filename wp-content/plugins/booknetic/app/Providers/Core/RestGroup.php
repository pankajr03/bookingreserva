<?php

namespace BookneticApp\Providers\Core;

class RestGroup
{
    private string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function get($route, $fn, $args = []): void
    {
        RestRoute::get(sprintf('%s/%s', $this->prefix, $route), $fn, $args);
    }

    public function post($route, $fn, $args = []): void
    {
        RestRoute::post(sprintf('%s/%s', $this->prefix, $route), $fn, $args);
    }

    public function put($route, $fn, $args = []): void
    {
        RestRoute::put(sprintf('%s/%s', $this->prefix, $route), $fn, $args);
    }

    public function delete($route, $fn, $args = []): void
    {
        RestRoute::delete(sprintf('%s/%s', $this->prefix, $route), $fn, $args);
    }
}
