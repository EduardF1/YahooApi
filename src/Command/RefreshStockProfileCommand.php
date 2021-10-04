<?php

namespace App\Command;

use App\Entity\Stock;
use App\Http\FinanceApiClientInterface;
use App\Http\YahooFinanceApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:refresh-stock-profile',
    description: 'Add a short description for your command',
)]
class RefreshStockProfileCommand extends Command
{
    protected static $defaultName = 'app:refresh-stock-profile';
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private FinanceApiClientInterface $financeApiClient;

    public function __construct(EntityManagerInterface    $entityManager,
                                FinanceApiClientInterface $financeApiClient,
                                SerializerInterface       $serializer)
    {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->financeApiClient = $financeApiClient;
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Retrieve a stock profile from the Yahoo Finance API. Update the record in the database.')
            ->addArgument('symbol', InputArgument::REQUIRED, 'Stock Symbol e.g. AMZN for Amazon')
            ->addArgument('region', InputArgument::REQUIRED, 'The region of the company e.g. US for United States');;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stockProfile = $this->financeApiClient->fetchStockProfile(
            $input->getArgument('symbol'),
            $input->getArgument('region')
        );

        if ($stockProfile->getStatusCode() !== 200) {
            $output->writeln($stockProfile->getContent());
            return Command::FAILURE;
        }

        /** @var Stock $stock */
        $stock = $this->serializer->deserialize($stockProfile->getContent(), Stock::class, 'json');

        $this->entityManager->persist($stock);
        $this->entityManager->flush();

        $output->writeln($stock->getShortName() . ' has been saved/updated.');

        return Command::SUCCESS;
    }
}
