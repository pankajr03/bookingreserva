<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class CSVFileException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Please select CSV file!'));
    }
}
