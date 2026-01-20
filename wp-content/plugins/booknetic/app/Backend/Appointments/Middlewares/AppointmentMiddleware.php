<?php

namespace BookneticApp\Backend\Appointments\Middlewares;

use BookneticApp\Backend\Appointments\Helpers\AppointmentService;

class AppointmentMiddleware
{
    public function handle($next)
    {
        AppointmentService::cancelUnpaidAppointments();

        return $next();
    }
}
