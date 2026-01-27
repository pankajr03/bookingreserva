<?php

namespace BookneticApp\Providers\FSCode\Clients;

use BookneticApp\Providers\Helpers\Helper;

final class FSCodeAPIClientFactory
{
    public function make(): FSCodeAPIClient
    {
        $dto = $this->getContext();

        return new FSCodeAPIClient($dto);
    }

    private function getContext(): FSCodeAPIClientContextDto
    {
        $license  = Helper::getOption('purchase_code', '', false);
        $website  = site_url();
        $version  = Helper::getVersion();

        return new FSCodeAPIClientContextDto(
            $license,
            $website . '?q='.uniqid(),
            $version,
            PHP_VERSION,
            get_bloginfo('version')
        );
    }
}
