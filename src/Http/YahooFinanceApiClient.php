<?php

namespace App\Http;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class YahooFinanceApiClient
{
    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;
    private const URL = 'https://yh-finance.p.rapidapi.com/auto-complete';
    private const X_RAPID_API_HOST = 'yh-finance.p.rapidapi.com';
    private $rapidApiKey;

    public function __construct(HttpClientInterface $httpClient, $rapidApiKey)
    {
        $this->httpClient = $httpClient;
        $this->rapidApiKey = $rapidApiKey;
    }

    public function fetchStockProfile($symbol, $region)
    {
        $response = $this->httpClient->request('GET', self::URL, [
            'query' => [
                // 'q' stands for qualifier and replaces the old API's symbol parameter
                'q' => $symbol,
                'region' => $region
            ],
            'headers' => [
                'x-rapidapi-host' => self::X_RAPID_API_HOST,
                'x-rapidapi-key'=> $this->rapidApiKey
            ]
        ]);
    }
}