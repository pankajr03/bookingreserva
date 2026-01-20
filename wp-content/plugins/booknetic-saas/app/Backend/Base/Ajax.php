<?php

namespace BookneticSaaS\Backend\Base;

use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\IoC\Container;
use BookneticSaaS\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    public function ping()
    {
        return $this->response(true);
    }

    public function join_beta()
    {
        $apiClient = Container::get(FSCodeAPIClient::class);

        $apiClient->requestNew('booknetic-saas/product/join_beta', 'POST');

        Helper::setOption('joined_beta', true);

        return $this->response(true);
    }
}
