<?php

namespace App\Tests;

use App\Entity\Stock;

class StockTest extends DatabaseDependentTestCase
{
    /** @test */
    public function a_stock_record_can_be_created_in_the_database(){
        // Arrange

        // Stock
        $stock = new Stock();

        $stock->setSymbol('AMZN');
        $stock->setShortName('Amazon Inc');
        $stock->setCurrency('USD');
        $stock->setExchangeName('Nasdaq');
        $stock->setRegion('US');
        $price = 1000;
        $previousClose = 1100;
        $priceChange = $price - $previousClose;
        $stock->setPrice($price);
        $stock->setPreviousClose($previousClose);
        $stock->setPriceChange($priceChange);

        $this->entityManager->persist($stock);

        // Act
        $this->entityManager->flush();
        $stockRepository = $this->entityManager->getRepository(Stock::class);
        $stockRecord = $stockRepository->findOneBy(['symbol' => 'AMZN']);

        // Assert
        $this->assertEquals('Amazon Inc', $stockRecord->getShortName());
        $this->assertEquals('USD', $stockRecord->getCurrency());
        $this->assertEquals('Nasdaq', $stockRecord->getExchangeName());
        $this->assertEquals('US', $stockRecord->getRegion());
        $this->assertEquals(1000, $stockRecord->getPrice());
        $this->assertEquals(1100, $stockRecord->getPreviousClose());
        $this->assertEquals(-100, $stockRecord->getPriceChange());
    }
}