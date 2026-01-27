<?php

namespace BookneticApp\Backend\Services\Controllers;

use BookneticApp\Backend\Services\Services\ServiceCategoryService;
use BookneticApp\Backend\Services\Services\ServiceService;
use BookneticApp\Providers\Core\RestRequest;

class ServiceRestController
{
    private ServiceService $service;
    private ServiceCategoryService $categoryService;
    public function __construct(ServiceService $service, ServiceCategoryService $categoryService)
    {
        $this->service = $service;
        $this->categoryService = $categoryService;
    }
    public function getServices(RestRequest $request): array
    {
        $search		= $request->param('q', '', RestRequest::TYPE_STRING);
        $category	= $request->param('category', '', RestRequest::TYPE_INTEGER);

        $services = $this->service->getServices($search, $category);

        return [
            'data' => $services,
        ];
    }

    public function getCategories(): array
    {
        $categories = $this->categoryService->getAllWithParent();

        return [
            'data' => $categories,
        ];
    }

    public function getExtras(RestRequest $request): array
    {
        $appointmentId			= $request->param('appointment_id', 0, RestRequest::TYPE_INTEGER);
        $serviceId	            = $request->param('service_id', 0, RestRequest::TYPE_INTEGER);

        $extras = $this->service->getExtras($serviceId, $appointmentId);

        return [
            'data' => $extras,
        ];
    }
}
