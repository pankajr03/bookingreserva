<?php

namespace BookneticApp\Backend\Workflow\DTOs\Request;

class SaveCustomerBirthdayEventRequest
{
    public array $months;
    public array $years;
    public string $gender;
    public string $offsetSign;
    public string $offsetValue;
    public string $inputTime;

    public array $categories;
}
