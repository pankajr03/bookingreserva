<?php

namespace BookneticApp\Backend\Services\Services;

use BookneticApp\Backend\Appointments\Repositories\AppointmentExtraRepository;
use BookneticApp\Backend\Services\Repositories\ServiceExtraRepository;
use BookneticApp\Backend\Services\Repositories\ServiceRepository;
use BookneticApp\Providers\Helpers\Helper;

class ServiceService
{
    private ServiceRepository $serviceRepository;
    private AppointmentExtraRepository $appointmentExtraRepository;
    private ServiceExtraRepository $serviceExtraRepository;
    public function __construct(ServiceRepository $serviceRepository, AppointmentExtraRepository $appointmentExtraRepository, ServiceExtraRepository $serviceExtraRepository)
    {
        $this->serviceRepository = $serviceRepository;
        $this->appointmentExtraRepository = $appointmentExtraRepository;
        $this->serviceExtraRepository = $serviceExtraRepository;
    }
    public function getServices(string $search, int $category): array
    {
        return $this->serviceRepository->getServices($search, $category);
    }

    public function getExtras(int $serviceId, int $appointmentId): array
    {
        $showAllExtras =  Helper::getOption('show_all_service_extras', 'on');

        if ($showAllExtras == 'on') {
            $extras = $this->serviceExtraRepository->getAll();
        } else {
            $extras = $this->serviceExtraRepository->getAll($serviceId);
        }

        $appointmentExtras = $this->appointmentExtraRepository->getAllExtrasByAppointmentId($appointmentId);
        $appointmentExtras = Helper::assocByKey($appointmentExtras, 'extra_id');

        foreach ($extras as $extra) {
            $extra->quantity = array_key_exists($extra->id, $appointmentExtras) ? $appointmentExtras[$extra->id]->quantity : 0;
        }

        return $extras;
    }
}
