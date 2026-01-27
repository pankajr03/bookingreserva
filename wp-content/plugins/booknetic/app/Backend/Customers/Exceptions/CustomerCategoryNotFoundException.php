<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class CustomerCategoryNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct("Customer Category not found");
    }
}
