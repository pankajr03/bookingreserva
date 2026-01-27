<?php

namespace BookneticApp\Backend\Mobile\Controllers;

use BookneticApp\Backend\Mobile\Services\PlanService;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Request\Post;

class PlanController extends Controller
{
    private PlanService $service;

    public function __construct(PlanService $service)
    {
        $this->service = $service;
    }

    public function get_plan()
    {
        $id = Post::int('id');

        $plan = $this->service->get($id);

        return $this->response(true, [
            'plan' => $plan
        ]);
    }
}
