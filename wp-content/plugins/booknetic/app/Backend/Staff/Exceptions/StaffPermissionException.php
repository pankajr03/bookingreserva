<?php

namespace BookneticApp\Backend\Staff\Exceptions;

class StaffPermissionException extends StaffException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: bkntc__('You do not have permission to perform this action.'));
    }
}
