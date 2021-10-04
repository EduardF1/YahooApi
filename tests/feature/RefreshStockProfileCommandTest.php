<?php

namespace App\Tests\feature;

use App\Entity\Stock;
use App\Http\YahooFinanceApiClientMock;
use App\Tests\DatabaseDependentTestCase;
use App\Tests\DatabasePrimer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class RefreshStockProfileCommandTest extends DatabaseDependentTestCase
{
    /** @test */
    public function the_refresh_stock_profile_command_behaves_correctly_when_a_stock_record_does_not_exist()
    {
        // Arrange
        $application = new Application(self::$kernel);

        // Command
        $command = $application->find('app:refresh-stock-profile');
        $commandTester = new CommandTester($command);

        // Set mocked return content (response)
        YahooFinanceApiClientMock::$content = '{
            "symbol":"AMZN",
            "shortName":"Amazon.com, Inc.",
            "region":"US",
            "exchangeName":"NasdaqGS",
            "currency":"USD",
            "price":3197.99,
            "previousClose":3283.26,
            "priceChange":-85.27
            }';

        // Act
        $commandTester->execute([
            'symbol' => 'AMZN',
            'region' => 'US'
        ]);
        $repository = $this->entityManager->getRepository(Stock::class);
        /** @var Stock $stock */
        $stock = $repository->findOneBy(['symbol' => 'AMZN']);

        // Assert
        $this->assertSame('Amazon.com, Inc.', $stock->getShortName());
        $this->assertSame('USD', $stock->getCurrency());
        $this->assertSame('AMZN', $stock->getSymbol());
        $this->assertSame('NasdaqGS', $stock->getExchangeName());
        $this->assertSame('US', $stock->getRegion());
        $this->assertGreaterThan(50, $stock->getPreviousClose());
        $this->assertGreaterThan(50, $stock->getPrice());
        $this->assertStringContainsString('Amazon.com, Inc. has been saved/updated', $commandTester->getDisplay());
    }

    /** @test */
    public function non_200_status_code_responses_are_handled_correctly()
    {
        // Arrange
        $application = new Application(self::$kernel);

        // Command
        $command = $application->find('app:refresh-stock-profile');
        $commandTester = new CommandTester($command);

        // Non 200 response
        YahooFinanceApiClientMock::$statusCode = 500;
        // Error content
        YahooFinanceApiClientMock::$content = 'Finance API Client Error ';

        // Act
        $commandStatus = $commandTester->execute([
            'symbol' => 'AMZN',
            'region' => 'US'
        ]);
        $repository = $this->entityManager->getRepository(Stock::class);
        $stockRecordCount = $repository->createQueryBuilder('stock')
            ->select('count(stock.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Assert
        $this->assertEquals(1, $commandStatus);
        $this->assertEquals(0, $stockRecordCount);
        $this->assertStringContainsString('Finance API Client Error', $commandTester->getDisplay());
    }
}