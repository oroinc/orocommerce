<?php
declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reorganizes price list database tables to use or forgo sharding.
 */
class PriceListStorageReorganizeCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:price-lists:pl-storage-reorganize';

    private ShardManager $shardManager;

    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('entity-alias')
            ->addOption('strategy', null, InputOption::VALUE_REQUIRED, 'Can be "base" or "sharding"')
            ->setDescription('Reorganizes price list database tables to use or forgo sharding.')
            ->setHelp(
                // @codingStandardsIgnoreStart
                <<<'HELP'
The <info>%command.name%</info> command reorganizes price list database tables
to use or forgo sharding. After running this command make sure to modify
the <comment>enable_price_sharding</comment> option in <comment>config/parameters.yml</comment> to a matching value.

Use <info>--strategy=sharding</info> to reorganize database tables using sharding for prices:

  <info>php %command.full_name% --strategy=sharding prices</info>

Use <info>--strategy=base</info> to reorganize database tables without sharding for prices:

  <info>php %command.full_name% --strategy=base prices</info>

Run the command without arguments to see the list of all supported entities:

  <info>php %command.full_name%</info>

HELP
                // @codingStandardsIgnoreEnd
            )
            ->addUsage('--strategy=sharding prices')
            ->addUsage('--strategy=base prices')
            ->addUsage('--strategy=sharding <entity-alias>')
            ->addUsage('--strategy=base <entity-alias>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $alias = $input->getArgument('entity-alias');

        $shardList = $this->shardManager->getShardList();

        if (!$alias || !array_key_exists($alias, $shardList)) {
            $output->writeln("<error>Entity alias required. Select one from the list:</error>");
            foreach ($shardList as $alias => $className) {
                $output->writeln('<info>' . $alias . ' : ' . $className . '</info>');
            }
            return self::FAILURE;
        }

        $strategy = $input->getOption('strategy');

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

            return self::FAILURE;
        }

        $output->writeln(
            sprintf("<info>Storage for class %s reorganized for %s strategy</info>", $className, $strategy)
        );
        if ($strategy === 'base') {
            $output->writeln('Do not forget change config/parameters.yml enable_price_sharding: false');
        } else {
            $output->writeln('Do not forget change config/parameters.yml enable_price_sharding: true');
        }

        return self::SUCCESS;
    }
}
