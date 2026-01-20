<?php

namespace BookneticApp\Backend\Staff\Exceptions;

use Exception;

abstract class StaffException extends \Exception
{
    protected array $data = [];

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message ?: bkntc__('An unexpected staff error occurred.'), $code, $previous);
    }

    /**
     * Set custom response data.
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data): StaffException
    {
        $this->data = $data;

        return $this;
    }
}
