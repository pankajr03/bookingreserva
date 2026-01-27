<?php

namespace BookneticApp\Backend\Staff\Exceptions;

class StaffNotFoundException extends StaffException
{
    public function __construct(?int $staffId = null)
    {
        $message = $staffId
            ? bkntc__('Staff with ID %d not found.', [$staffId])
            : bkntc__('Staff not found.');

        parent::__construct($message);
    }
}
