<?php

namespace BookneticApp\Backend\Appearance\Controllers;

use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Backend\Appearance\Services\AppearanceService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;

class AppearanceController extends Controller
{
    private AppearanceService $service;

    public function __construct()
    {
        $this->service = new AppearanceService();
    }

    /**
     * @throws CapabilitiesException
     */
    public function index(): void
    {
        Capabilities::must('appearance');

        $listResponse = $this->service->getAllAppearances();

        $this->view('index', $listResponse);
    }

    /**
     * @throws CapabilitiesException
     */
    public function edit(): void
    {
        $id = Helper::_get('id', '0', 'int');

        $appearanceResponse = $this->service->edit($id);

        $this->view('edit', $appearanceResponse);
    }
}
