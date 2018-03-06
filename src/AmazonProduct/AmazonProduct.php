<?php

namespace Semok\Api\AmazonProduct;

use SemokLog;
use Exception;
use Semok\Api\AmazonProduct\Exceptions\RequestException;
use Semok\Api\AmazonProduct\Exceptions\LimitException;

class AmazonProduct
{
	private $urlBuilder = null;
	private $config = null;
	private $filter;
	private $itemSearchOptions;
	private $itemLookupOptions;

	// Valid names that can be used for search
	private $mValidSearchNames = [
		'All',					'Apparel',				'Appliances',
		'Automotive',			'Baby',					'Beauty',
		'Blended',				'Books',				'Classical',
		'DVD',					'Electronics',			'Grocery',
		'HealthPersonalCare',	'HomeGarden',			'HomeImprovement',
		'Jewelry',				'KindleStore',			'Kitchen',
		'Lighting',				'Marketplace',			'MP3Downloads',
		'Music',				'MusicTracks',			'MusicalInstruments',
		'OfficeProducts',		'OutdoorLiving',		'Outlet',
		'PetSupplies',			'PCHardware',			'Shoes',
		'Software',				'SoftwareVideoGames',	'SportingGoods',
		'Tools',				'Toys',					'VHS',
		'Video',				'VideoGames',			'Watches'
	];

    public function __construct()
    {
        $this->config = config('semok.api.amazonproduct');
    }

	protected function urlBuilder()
	{
		if (!$this->config['random_api']) {
			return new AmazonUrlBuilder(
	            $this->config['access_key'],
	            $this->config['secret_key'],
	            $this->config['associate_tag'],
	            $this->config['region']
	        );
		}

		if (!is_array($this->config['apis']) || empty($this->config['apis'])) {
            throw new RequestException('API amazon kosong');
        }

        $amazonApi = array_random($this->config['apis']);

        if (!isset($amazonApi['access_key']) || empty($amazonApi['access_key'])) {
            throw new RequestException('API amazon (access_key) kosong');
        }

        if (!isset($amazonApi['secret_key']) || empty($amazonApi['secret_key'])) {
            throw new RequestException('API amazon (secret_key) kosong');
        }

        if (!isset($amazonApi['associate_tag']) || empty($amazonApi['associate_tag'])) {
            throw new RequestException('API amazon (associate_tag) kosong');
        }
        return new AmazonUrlBuilder(
            $amazonApi['access_key'],
            $amazonApi['secret_key'],
            $amazonApi['associate_tag'],
            $this->config['region']
        );
	}

	public function itemSearch($keyword, $options = null, $filter = null) {
		$this->keyword = $keyword;
        $this->itemSearchOptions = $options;
        $this->filter = $filter;
		$this->prepareItemSearchRequest();
		$this->urlBuilder = $this->urlBuilder();
		$results = $this->makeAndParseRequest($this->itemSearchOptions);
		$this->verifyErrorResponse($results);

		if (isset($results['Items']['Item']['ASIN'])) {
			$results['Items']['Item'] = [$results['Items']['Item']];
		}
		if ($this->filter) {
			$items = [];
			foreach ($results['Items']['Item'] as $content) {
				try {
                    $result = (app()->make($this->filter))->runItemSearchFilter($content);
                    if ($result) $items[] = $result;
                } catch (Exception $e) {
                    SemokLog::file('api')->error('AmazonProductApi: Apply Filter: ' . $e->getMessage());
                }
			}
			if (empty($items)) {
				throw new RequestException('Empty Result after filter applied');
			}
			$results['Items']['Item'] = $items;
		}

		return $results['Items'];
	}

	public function itemLookup($asinList, $options = null, $filter = null) {
		$this->asinList = $asinList;
        $this->itemLookupOptions = $options;
        $this->itemLookupFilter = $filter;
		$this->prepareItemLookupRequest();
		$this->urlBuilder = $this->urlBuilder();
		$results = $this->makeAndParseRequest($this->itemLookupOptions);
		$this->verifyErrorResponse($results);

		if (isset($results['Items']['Item']['ASIN'])) {
			$results['Items']['Item'] = [$results['Items']['Item']];
		}

		if ($this->filter) {
			$items = [];
			foreach ($results['Items']['Item'] as $content) {
				try {
					$result = (app()->make($this->filter))->runItemLookupFilter($content);
                    if ($result) $items[] = $result;
                } catch (Exception $e) {
                    SemokLog::file('api')->error('AmazonProductApi: Apply Filter: ' . $e->getMessage());
                }
			}
			if (empty($items)) {
				throw new RequestException('Empty Result after filter applied');
			}
			$results['Items']['Item'] = $items;
		}

		return $results['Items'];
	}

    protected function prepareItemSearchRequest()
    {
        if (is_array($this->itemSearchOptions)) {
            $this->itemSearchOptions  = array_merge($this->config['itemSearchOptions'], $this->itemSearchOptions);
        } else {
            $this->itemSearchOptions = $this->config['itemSearchOptions'];
        }
        if (!$this->filter && $this->config['filter']) {
            $this->filter = $this->config['filter'];
        }

		$this->itemSearchOptions['Keywords'] = $this->keyword;

		if (empty($this->itemSearchOptions['SearchIndex'])) {
			$this->itemSearchOptions['SearchIndex'] = 'All';
		}
		if (!$this->itemSearchOptions['Sort'] || $this->itemSearchOptions['SearchIndex'] == 'All') {
			$this->itemSearchOptions['Sort'] = null;
		}
    }

    protected function prepareItemLookupRequest()
    {
        if (is_array($this->itemLookupOptions)) {
            $this->itemLookupOptions  = array_merge($this->config['itemLookupOptions'], $this->itemLookupOptions);
        } else {
            $this->itemLookupOptions = $this->config['itemLookupOptions'];
        }
        if (!$this->filter && $this->config['filter']) {
            $this->filter = $this->config['filter'];
        }

		$this->itemLookupOptions['ItemId'] = $this->asinList;
    }

	private function makeAndParseRequest($params) {
		$signedUrl = $this->urlBuilder->generate($params);

		try {
			$request = new CurlHttpRequest();
			$response = $request->execute($signedUrl);
			$parsedXml = simplexml_load_string($response);
			if ($parsedXml === false) {
				throw new RequestException("Unknown response");
			}
			$json = json_encode($parsedXml);
	        return (json_decode($json, true));
		} catch(\Exception $error) {
			throw new RequestException("Error downloading data : $signedUrl : " . $error->getMessage());
		}
	}

	protected function verifyErrorResponse($results)
	{
		if (isset($results['Error'])) {
			if ($results['Error']['Code'] == 'RequestThrottled') {
				throw new LimitException("{$results['Error']['Code']} : {$results['Error']['Message']}");
			}
			throw new RequestException("{$results['Error']['Code']} : {$results['Error']['Message']}");
		}

		if (isset($results['Items']['Request']['Errors']['Error'])) {
			$error = $results['Items']['Request']['Errors']['Error'];
			if ($error['Code'] == 'RequestThrottled') {
				throw new LimitException("{$error['Code']} : {$error['Message']}");
			}
			throw new RequestException("{$error['Code']} : {$error['Message']}");
		}

		if (
			!isset($results['Items']['Item']) ||
			!is_array($results['Items']['Item']) ||
			empty($results['Items']['Item'])
		) {
            throw new RequestException('Empty Result');
        }
	}
}
