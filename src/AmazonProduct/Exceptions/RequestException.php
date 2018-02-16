<?php

namespace Semok\Api\AmazonProduct\Exceptions;

use Semok\Support\Exceptions\RuntimeException;

class RequestException extends RuntimeException
{
    protected $filename = 'semok/api/amazonproduct.log';
}
