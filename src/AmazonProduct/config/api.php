<?php return [
    'region' => 'uk',
    'access_key' => null,
    'secret_key' => 'FjmfU1d9MgZGHCdOOZdQdsMCiXy1kigzCSYNFxZC',
    'associate_tag' => 'satu-21',
    'random_api' => false,
    'filter' => Semok\Api\AmazonProduct\Filter\BaseFilter::class,
    'itemSearchOptions' => [
        'Operation' => 'ItemSearch',
        'ResponseGroup' => 'ItemAttributes,BrowseNodes,Similarities,EditorialReview,Images',
        'Condition' => 'New',
        'SearchIndex' => 'All',
        'Sort' => null
    ],
    'itemLookupOptions' => [
        'Operation' => 'ItemLookup',
        'ResponseGroup' => 'ItemAttributes,BrowseNodes,Similarities,EditorialReview,Images',
        'MerchantId' => 'All'
    ],
    'apis' => [
        [
            'access_key' => null,
            'secret_key' => null,
            'associate_tag' => null,
        ]
    ]
];
