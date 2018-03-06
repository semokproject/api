<?php

namespace Semok\Api\AmazonProduct\Exceptions;

use Semok\Support\Exceptions\RuntimeException;

class RequestException extends RuntimeException
{
    public $filename = 'api.error.log';

    public function __construct($message, $code = 0, Exception $previous = null)
    {
        $message = 'AmazonProductApi: ' . $message;
        parent::__construct($message, $code, $previous);
    }
}
