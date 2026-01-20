<?php

namespace BookneticSaaS\Backend\Settings\Exceptions;

use Exception;

class SplitPaymentNotSupportedException extends Exception
{
    public function __construct($message = null, $code = 403)
    {
        $message = $message ?? bkntcsaas__("You don't have any Split Payment supported gateways!");
        parent::__construct($message, $code);
    }
}
