<?php

namespace BookneticApp\Backend\Staff\Controllers;

use BookneticApp\Backend\Staff\Services\StaffService;
use BookneticApp\Providers\Core\RestRequest;

class StaffRestController
{
    private StaffService $staffService;
    public function __construct(StaffService $staffService)
    {
        $this->staffService = $staffService;
    }
    public function gelAllActive(RestRequest $request): array
    {
        $search		= $request->param('search', '', RestRequest::TYPE_STRING);
        $location	= $request->param('location', 0, RestRequest::TYPE_INTEGER);
        $service    = $request->param('service', 0, RestRequest::TYPE_INTEGER);

        $staffList = $this->staffService->getStaffList($search, $location, $service);

        return [
            'data' => $staffList
        ];
    }
}
