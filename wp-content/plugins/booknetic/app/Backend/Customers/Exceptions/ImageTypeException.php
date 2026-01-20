<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class ImageTypeException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Only JPG and PNG images allowed!'));
    }
}
