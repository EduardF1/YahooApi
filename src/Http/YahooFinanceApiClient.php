<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YahooFinanceApiClient implements FinanceApiClientInterface
{
    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;
    private const URL = 'https://yh-finance.p.rapidapi.com/stock/v2/get-profile';
    private const X_RAPID_API_HOST = 'yh-finance.p.rapidapi.com';
    private string $rapidApiKey;

    public function __construct(HttpClientInterface $httpClient, $rapidApiKey)
    {
        $this->httpClient = $httpClient;
        $this->rapidApiKey = $rapidApiKey;
    }

    public function fetchStockProfile($symbol, $region): JsonResponse
    {
        try {
            $response = $this->httpClient->request('GET', self::URL, [
                'query' => [
                    'symbol' => $symbol,
                    'region' => $region
                ],
                'headers' => [
                    'x-rapidapi-host' => self::X_RAPID_API_HOST,
                    'x-rapidapi-key' => $this->rapidApiKey
                ]
            ]);
        } catch (TransportExceptionInterface $e) {
            echo 'Something went wrong, an exception ' . $e . ' has occurred';
        }

        if ($response->getStatusCode() !== 200) {
            return new JsonResponse('Finance API Client Error', 400);
        }

        // convert json response to PHP object and access the price property
        try {
            $stockProfile = json_decode($response->getContent())->price;
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
            echo 'Something went wrong, an exception ' . $e . ' has occurred';
        }

        $stockProfileAsArray = [
            'symbol' => $stockProfile->symbol,
            'shortName' => $stockProfile->shortName,
            'region' => $region,
            'exchangeName' => $stockProfile->exchangeName,
            'currency' => $stockProfile->currency,
            'price' => $stockProfile->regularMarketPrice->raw,
            'previousClose' => $stockProfile->regularMarketPreviousClose->raw,
            'priceChange' => $stockProfile->regularMarketPrice->raw - $stockProfile->regularMarketPreviousClose->raw
        ];
        return new JsonResponse($stockProfileAsArray, 200);
    }
}