<?php

namespace BookneticApp\Backend\Services\Exceptions;

use Exception;

class CategoryAlreadyExistException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('This category is already exist! Please choose an other name.'));
    }
}
