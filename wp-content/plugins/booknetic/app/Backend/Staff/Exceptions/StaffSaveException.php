<?php

namespace BookneticApp\Backend\Staff\Exceptions;

class StaffSaveException extends StaffException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: bkntc__('Failed to save staff record.'));
    }
}
