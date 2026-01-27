<?php

namespace BookneticApp\Backend\Workflow\DTOs\Request;

class SaveBookingStartsEventRequest
{
    public string $offsetSign;
    public int $offsetValue;
    public string $offsetType;
    public array $statuses;
    public array $locations;
    public array $services;
    public array $staffs;
    public string $locale;
    public bool $forEachCustomer;
    public array $categories;
}
