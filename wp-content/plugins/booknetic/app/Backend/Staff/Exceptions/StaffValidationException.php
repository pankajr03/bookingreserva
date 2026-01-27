<?php

namespace BookneticApp\Backend\Staff\Exceptions;

class StaffValidationException extends StaffException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: bkntc__('Staff validation failed.'));
    }
}
