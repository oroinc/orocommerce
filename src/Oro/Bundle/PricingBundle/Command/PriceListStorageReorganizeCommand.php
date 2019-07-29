<?php

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Change storage strategy for class
 */
class PriceListStorageReorganizeCommand extends Command
{
    private const STRATEGY = 'strategy';
    private const ENTITY_ALIAS = 'entity-alias';

    /** @var string */
    protected static $defaultName = 'oro:price-lists:pl-storage-reorganize';

    /** @var ShardManager */
    private $shardManager;

    /**
     * @param ShardManager $shardManager
     */
    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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

        $shardList = $this->shardManager->getShardList();

        if (!$alias || !array_key_exists($alias, $shardList)) {
            $output->writeln("<error>Entity alias required. Select one from the list:</error>");
            foreach ($shardList as $alias => $className) {
                $output->writeln('<info>' . $alias . ' : ' . $className . '</info>');
            }
            return 1;
        }

        $strategy = $input->getOption(self::STRATEGY);

        $className = $shardList[$alias];
        if ($strategy === "base") {
            $this->shardManager->moveDataFromShardsToBaseTable($className);
        } elseif ($strategy === "sharding") {
            $this->shardManager->moveDataFromBaseTableToShard($className);
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
            $output->writeln('Do not forget change config/parameters.yml enable_price_sharding: false');
        } else {
            $output->writeln('Do not forget change config/parameters.yml enable_price_sharding: true');
        }

        return 0;
    }
}
