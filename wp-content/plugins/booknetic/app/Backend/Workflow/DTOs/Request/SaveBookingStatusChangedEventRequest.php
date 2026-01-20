<?php

namespace BookneticApp\Backend\Workflow\DTOs\Request;

class SaveBookingStatusChangedEventRequest
{
    public array $statuses;
    public array $prevStatuses;
    public array $locations;
    public array $services;
    public array $staffs;
    public string $locale;
    public string $calledFrom;
    public array $categories;
}
