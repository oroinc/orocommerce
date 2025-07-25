<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prepares and activates combined price lists based on their schedules.
 */
#[AsCommand(
    name: 'oro:cron:price-lists:remove-unused',
    description: 'Removes unused combined price lists scheduled for removal'
)]
class RemoveUnusedCombinedPriceListsCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    private CombinedPriceListGarbageCollector $garbageCollector;

    public function __construct(
        CombinedPriceListGarbageCollector $garbageCollector
    ) {
        parent::__construct();
        $this->garbageCollector = $garbageCollector;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> removes unused combined price lists scheduled for removal.

  <info>php %command.full_name%</info>

HELP
            );
    }

    #[\Override]
    public function isActive(): bool
    {
        return $this->garbageCollector->hasPriceListsScheduledForRemoval();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->garbageCollector->removeScheduledUnusedPriceLists();

        return self::SUCCESS;
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        // At minute 7 past every hour to minimize overlapping with other crons or CPL recalculations.
        return '7 */1 * * *';
    }
}
