<?php

namespace BookneticApp\Backend\Locations\Controllers;

use BookneticApp\Backend\Locations\Services\LocationService;
use BookneticApp\Providers\Core\RestRequest;

class LocationRestController
{
    private LocationService $locationService;
    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }
    public function getMyAllEnabledLocations(RestRequest $request): array
    {
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $locations = $this->locationService->getMyAllEnabledLocations($search);

        return [
            'data' => $locations
        ];
    }
}
