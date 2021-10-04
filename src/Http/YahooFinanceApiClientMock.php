<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

class YahooFinanceApiClientMock implements FinanceApiClientInterface
{
    public static int $statusCode = 200;
    public static string $content = '';

    public function fetchStockProfile(string $symbol, string $region): JsonResponse
    {
        return new JsonResponse(self::$content, self::$statusCode, [], true);
    }
}

