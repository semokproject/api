<?php

namespace Semok\Api\GoogleBook;

use SemokLog;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Semok\Api\GoogleBook\Exceptions\RequestException;

class GoogleBook
{
	private $client;
	private $config = null;
	private $filter;
	private $options;
	private $requestOptions;

	public function __construct()
	{
		$this->config = config('semok.api.googlebook');
		$this->client = new Client(['base_uri' => 'https://www.googleapis.com/books/v1/']);
	}

	protected function getApiKey()
	{
		if (!$this->config['random_api']) {
			return $this->config['api_key'];
		}

		if (!is_array($this->config['apis']) || empty($this->config['apis'])) {
			throw new RequestException('API GoogleBook kosong');
		}

		$googleBookApi = array_random($this->config['apis']);

		if (empty($googleBookApi)) {
			throw new RequestException('API GoogleBook kosong');
		}
		return $googleBookApi;
	}

	public function volume($keyword, $options = null, $filter = null) {
		$this->keyword = $keyword;
		$this->options = $options;
		$this->filter = $filter;
		$googleBookApi = $this->getApiKey();
		$this->prepareVolumeRequest($googleBookApi);
		$results = $this->makeAndParseRequest('volumes');

		if (!$results->has('results') || !$results->get('results')->count()) {
			throw new RequestException('Empty Result');
		}
		if ($this->filter) {
			$items = collect([]);
			$results->get('results')->each(function($item) use($items){
				try {
					$result = app()->makeWith($this->filter, ['item' => $item])->handle();
					if ($result) $items->push($result);
				} catch (Exception $e) {
					SemokLog::file('api')->error('GoogleBookApi: Apply Filter: ' . $e->getMessage());
				}
			});
			$results->put('results', $items);
			if (!$results->get('results')->count()) {
				throw new RequestException('Empty Result after filter applied');
			}
		}

		return $results;
	}

	protected function prepareVolumeRequest($googleBookApi)
	{
		if (is_array($this->options)) {
			$this->options  = array_merge($this->config['options'], $this->options);
		} else {
			$this->options = $this->config['options'];
		}
		if (!$this->filter && $this->config['filter']) {
			$this->filter = $this->config['filter'];
		}

		$this->options['query']['q'] = $this->keyword;
		$this->options['query']['key'] = $googleBookApi;
		$this->options['http_errors'] = false;
	}


	private function makeAndParseRequest($path) {
		try {
			$options = $this->options;
			$options['on_stats'] = function (TransferStats $stats) use (&$url) {
				$url = $stats->getEffectiveUri()->__toString();
			};
			$response = $this->client->request('GET', $path, $options);
			$status = $response->getStatusCode();
			if ($status != 200) {
				throw new RequestException('Invalid response. Status: ' . $status . '. Body: ' . $response->getBody());
			}
			$results = json_decode($response->getBody(), true);
			return semok_collect([
				'attributes' => [
					'url' => $url,
                    'keyword' => $this->keyword,
                    'options' => $this->options,
                    'filter' => $this->filter
				],
				'results' => isset($results['items']) ? $results['items'] : [],
				'total' => isset($results['totalItems']) ? $results['totalItems'] : 0,
			]);
		} catch(Exception $error) {
			throw new RequestException("Error downloading data : " . $error->getMessage());
		}
	}
}
