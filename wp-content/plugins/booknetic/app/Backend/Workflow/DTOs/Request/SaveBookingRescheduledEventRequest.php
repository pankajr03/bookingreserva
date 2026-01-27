<?php

namespace BookneticApp\Backend\Workflow\DTOs\Request;

final class SaveBookingRescheduledEventRequest
{
    public array $locations;
    public array $services;
    public array $staffs;
    public string $locale;
    public bool $forEachCustomer;
    public string $calledFrom;
    public array $categories;
}
