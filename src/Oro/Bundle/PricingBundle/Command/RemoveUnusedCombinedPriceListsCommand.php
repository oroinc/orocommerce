<?php
declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prepares and activates combined price lists based on their schedules.
 */
class RemoveUnusedCombinedPriceListsCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:price-lists:remove-unused';

    private CombinedPriceListGarbageCollector $garbageCollector;

    public function __construct(
        CombinedPriceListGarbageCollector $garbageCollector
    ) {
        parent::__construct();
        $this->garbageCollector = $garbageCollector;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Removes unused combined price lists scheduled for removal')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> removes unused combined price lists scheduled for removal.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        return $this->garbageCollector->hasPriceListsScheduledForRemoval();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->garbageCollector->removeScheduledUnusedPriceLists();

        return self::SUCCESS;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        // At minute 7 past every hour to minimize overlapping with other crons or CPL recalculations.
        return '7 */1 * * *';
    }
}
