<?php

namespace Semok\Api\AmazonProduct\Filter;

class BaseFilter implements Filter
{
    public function runItemSearchFilter($result)
    {
        return $result;
    }

    public function runItemLookupFilter($result)
    {
        return $result;
    }
}
