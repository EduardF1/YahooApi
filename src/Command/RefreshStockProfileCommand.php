<?php

namespace App\Command;

use App\Entity\Stock;
use App\Http\FinanceApiClientInterface;
use App\Http\YahooFinanceApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    private LoggerInterface $logger;

    public function __construct(LoggerInterface           $logger,
                                SerializerInterface       $serializer,
                                EntityManagerInterface    $entityManager,
                                FinanceApiClientInterface $financeApiClient,
    )
    {
        $this->logger = $logger;
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
        try {
            $stockProfile = $this->financeApiClient->fetchStockProfile(
                $input->getArgument('symbol'),
                $input->getArgument('region')
            );

            if ($stockProfile->getStatusCode() !== 200) {
                $output->writeln($stockProfile->getContent());
            }
            // Attempt to find a record in the database using the $stockProfile symbol
            $symbol = json_decode($stockProfile->getContent())->symbol ?? null;

            if ($stock = $this->entityManager->getRepository(Stock::class)->findOneBy(['symbol' => $symbol])) {
                $this->serializer->deserialize($stockProfile->getContent(),
                    Stock::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $stock]);
            } else {
                $stock = $this->serializer->deserialize($stockProfile->getContent(),
                    Stock::class, 'json');
            }

            $this->entityManager->persist($stock);
            $this->entityManager->flush();

            $output->writeln($stock->getShortName() . ' has been saved/updated.');

            return Command::SUCCESS;

        } catch (\Exception $exception) {
            $this->logger->warning(get_class($exception) . ': ' . $exception->getMessage()
                . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine() . ' using [symbol/region]'
                . '[' . $input->getArgument('symbol') . '/' . $input->getArgument('region') . ']');
            return Command::FAILURE;
        }
    }
}
