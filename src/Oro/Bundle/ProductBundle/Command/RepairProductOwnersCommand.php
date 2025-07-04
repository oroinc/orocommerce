<?php

namespace Oro\Bundle\ProductBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for repairing products owners during to it`s organization
 * to bypass errors caused by product owned to business unit from other organization
 */
class RepairProductOwnersCommand extends Command
{
    protected static $defaultName = 'oro:product:repair-owners';

    private const BATCH_SIZE = 100;

    public function __construct(
        private ManagerRegistry $doctrine
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure()
    {
        $this->addOption(name: 'batch-size', mode: InputOption::VALUE_OPTIONAL, default: self::BATCH_SIZE)
            ->setDescription(
                'Repairing products owners during to it`s organization ' .
                'to bypass errors caused by product owned to business unit from other organization.'
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $successProducts = [];
        $failureProducts = [];
        $batchSize = $input->getOption('batch-size');

        $em = $this->doctrine->getManagerForClass(Product::class);
        $repo = $em->getRepository(Product::class);
        $qb = $repo->createQueryBuilder('p');

        $query = $qb->select('p')
            ->join('p.owner', 'bu')
            ->andWhere($qb->expr()->neq('IDENTITY(p.organization)', 'IDENTITY(bu.organization)'))
            ->getQuery();

        $it = new BufferedQueryResultIterator($query);
        $it->setBufferSize($batchSize);

        try {
            /** @var Product $product */
            foreach ($it as $key => $product) {
                $organization = $product->getOrganization();
                $organizationFirstBusinessUnit = $organization->getBusinessUnits()?->first();

                if (!$organizationFirstBusinessUnit) {
                    $failureProducts[$product->getId()] = $product->getSku();
                    continue;
                }

                $product->setOwner($organizationFirstBusinessUnit);
                $successProducts[$product->getId()] = $product->getSku();
                $em->persist($product);

                if (($key + 1) % $batchSize === 0) {
                    $em->flush();
                    $em->clear();
                }
            }

            $em->flush();
        } catch (\Throwable $exception) {
            $output->writeln('<error>Can\'t flush product changes.</error>');
            $output->writeln($exception->getTraceAsString());

            return self::FAILURE;
        }

        if (count($successProducts) > 0) {
            $output->writeln(
                sprintf(
                    '<info>Owner upadted for products: %s</info>',
                    implode(', ', $successProducts)
                )
            );
        }

        if (count($failureProducts) > 0) {
            $output->writeln(
                sprintf(
                    '<warning>Owner not upadted for products(no business units in product organization): %s</warning>',
                    implode(', ', $failureProducts)
                )
            );
        }

        return self::SUCCESS;
    }
}
