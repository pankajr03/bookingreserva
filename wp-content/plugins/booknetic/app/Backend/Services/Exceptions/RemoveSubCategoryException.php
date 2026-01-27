<?php

namespace BookneticApp\Backend\Services\Exceptions;

use Exception;

class RemoveSubCategoryException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Firstly remove sub categories!'));
    }
}
