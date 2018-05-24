<?php

namespace Semok\Api\GoogleBook\Exceptions;

use Semok\Support\Exceptions\RuntimeException;

class RequestException extends RuntimeException
{
    public $filename = 'api.error.log';

    public function __construct($message, $code = 0, Exception $previous = null)
    {
        $message = 'GoogleBook: ' . $message;
        parent::__construct($message, $code, $previous);
    }
}
