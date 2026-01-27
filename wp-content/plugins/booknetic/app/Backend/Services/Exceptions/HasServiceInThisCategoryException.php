<?php

namespace BookneticApp\Backend\Services\Exceptions;

use Exception;

class HasServiceInThisCategoryException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('There are some services in this category.'));
    }
}
