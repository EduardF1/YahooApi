<?php
namespace App\Tests\integration;

use App\Tests\DatabaseDependentTestCase;

class YahooFinanceApiClientTest extends DatabaseDependentTestCase {
    /**
     * @test
     * @group integration
     */
    public function the_yahoo_finance_api_client_returns_the_correct_data(){
        // Arrange
        $yahooFinanceApiClient = self::$kernel->getContainer()->get('yahoo-finance-api-client');
        // Act
        $response = $yahooFinanceApiClient->fetchStockProfile('AMZN', 'US'); // symbol, region
        $stockProfile = json_decode($response->getContent());

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('AMZN', $stockProfile->symbol);
        $this->assertSame('Amazon.com, Inc.', $stockProfile->shortName);
        $this->assertSame('US', $stockProfile->region);
        $this->assertSame('NasdaqGS', $stockProfile->exchangeName);
        $this->assertSame('USD', $stockProfile->currency);
        $this->assertIsFloat($stockProfile->price);
        $this->assertIsFloat($stockProfile->previousClose);
        $this->assertIsFloat($stockProfile->priceChange);
    }
}