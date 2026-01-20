<?php

declare(strict_types=1);

namespace BookneticApp\Backend\Workflow\DTOs\Request;

final class SaveNewBookingEventRequest
{
    public array $locations;
    public array $services;
    public array $staffs;
    public array $statuses;
    public string $locale;
    public string $calledFrom;
    public array $categories;
}
