<?php

namespace BookneticApp\Backend\Appointments\Controllers;

use BookneticApp\Backend\Appointments\Services\AppointmentDataTableService;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;

class AppointmentController extends Controller
{
    private AppointmentDataTableService $tableService;

    public function __construct(AppointmentDataTableService $tableService)
    {
        $this->tableService = $tableService;
    }
    /**
     * @throws CapabilitiesException
     */
    public function index(): void
    {
        $table = $this->tableService->getTable();
        $table = $table->renderHTML();

        $this->view('index', [ 'table' => $table ]);
    }
}
