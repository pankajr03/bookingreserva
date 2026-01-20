<?php

namespace BookneticSaaS\Backend\Settings\Exceptions;

use Exception;

class SettingsNotFoundException extends Exception
{
    public function __construct($message = null, $code = 404)
    {
        $message = $message ?? bkntcsaas__('Selected settings not found!');
        parent::__construct($message, $code);
    }
}
