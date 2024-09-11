<?php

namespace Wyue\Exceptions;

use Exception;
use Throwable;

class ApiException extends Exception
{
    public function __construct($message = "Internal Server Error", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}