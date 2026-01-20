<?php

namespace BookneticApp\Backend\Mobile\Services;

use BookneticApp\Backend\Mobile\Clients\FSCodeMobileAppClient;
use BookneticApp\Backend\Mobile\Mappers\PlanMapper;

class PlanService
{
    private FSCodeMobileAppClient $client;

    public function __construct(FSCodeMobileAppClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        $response = $this->client->getPlans();

        return PlanMapper::map($response->getData());
    }

    /**
     * @param int $planId
     * @return array
     */
    public function get(int $planId): array
    {
        $response = $this->client->getPlan($planId);

        return $response->getData();
    }
}
