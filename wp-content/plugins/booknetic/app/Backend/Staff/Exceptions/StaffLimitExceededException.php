<?php

namespace BookneticApp\Backend\Staff\Exceptions;

class StaffLimitExceededException extends StaffException
{
    public function __construct(int $limit)
    {
        parent::__construct(bkntc__('Staff limit (%d) exceeded.', [$limit]));
    }
}
