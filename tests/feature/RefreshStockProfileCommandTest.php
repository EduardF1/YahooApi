<?php

namespace App\Tests\feature;

use App\Entity\Stock;
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

        // Act
        $commandTester->execute([
            'symbol' => 'AMZN',
            'region' => 'US'
        ]);

        // Assert
        $repository = $this->entityManager->getRepository(Stock::class);

        /** @var Stock $stock */
        $stock = $repository->findOneBy(['symbol' => 'AMZN']);

        $this->assertSame('Amazon.com, Inc.', $stock->getShortName());
        $this->assertSame('USD', $stock->getCurrency());
        $this->assertSame('AMZN', $stock->getSymbol());
        $this->assertSame('NasdaqGS', $stock->getExchangeName());
        $this->assertSame('US', $stock->getRegion());
        $this->assertGreaterThan(50, $stock->getPreviousClose());
        $this->assertGreaterThan(50, $stock->getPrice());
    }
}