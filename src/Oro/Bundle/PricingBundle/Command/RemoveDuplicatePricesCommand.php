<?php

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Removes duplicate product prices cron command.
 */
#[AsCommand(name: 'oro:cron:prices:gc', description: 'Removes duplicated product prices.')]
class RemoveDuplicatePricesCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    public function __construct(
        private CombinedPriceListGarbageCollector $garbageCollector
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setHelp(
            <<<'HELP'
The <info>%command.name%</info> remove duplicated prices.

  <info>php %command.full_name%</info>

HELP
        );
    }

    public function getDefaultDefinition(): string
    {
        return '0 3 * * *';
    }

    public function isActive(): bool
    {
        return $this->garbageCollector->hasDuplicatePrices();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->garbageCollector->removeDuplicatePrices();

        return self::SUCCESS;
    }
}
