<?php

namespace Oro\Bundle\PricingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PriceListStorageReorganizeCommand extends ContainerAwareCommand
{
    const NAME = 'oro:price-lists:pl-storage-reorganize';
    const STRATEGY = 'strategy';
    const ENTITY_ALIAS = 'entity-alias';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addArgument(
                self::ENTITY_ALIAS
            )
            ->addOption(
                self::STRATEGY,
                null,
                InputOption::VALUE_REQUIRED,
                'Strategy can be "base" or "sharding"'
            )
            ->setDescription('Change storage strategy for class');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $alias = $input->getArgument(self::ENTITY_ALIAS);

        $shardManager = $this->getContainer()->get("oro_pricing.shard_manager");
        $shardList = $shardManager->getShardList();

        if (!$alias || !array_key_exists($alias, $shardList)) {
            $output->writeln("<error>Entity alias requeired. Select one from the list:</error>");
            foreach ($shardList as $alias => $className) {
                $output->writeln('<info>' . $alias . ' : ' . $className . '</info>');
            }
            return 1;
        }

        $strategy = $input->getOption(self::STRATEGY);
        $shardManager = $this->getContainer()->get("oro_pricing.shard_manager");

        $className = $shardList[$alias];
        if ($strategy === "base") {
            $shardManager->moveDataFromShardsToBaseTable($className);
        } elseif ($strategy === "sharding") {
            $shardManager->moveDataFromBaseTableToShard($className);
        } else {
            if (null === $strategy) {
                $output->writeln("<error>Missing strategy option. Strategy can be \"base\" or \"sharding\"</error>");
            } else {
                $output->writeln(sprintf("<error>Strategy '%s' not supported</error>", $strategy));
            }

            return 1;
        }

        $output->writeln(
            sprintf("<info>Storage for class %s reorganized for %s strategy</info>", $className, $strategy)
        );
        if ($strategy === 'base') {
            $output->writeln('Do not forget change app/config/parameters.yml enable_price_sharding: false');
        } else {
            $output->writeln('Do not forget change app/config/parameters.yml enable_price_sharding: true');
        }

        return 0;
    }
}
