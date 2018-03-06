<?php

namespace Semok\Api\AmazonProduct\Filter;

interface Filter
{
    public function runItemSearchFilter($result);
    
    public function runItemLookupFilter($result);
}
