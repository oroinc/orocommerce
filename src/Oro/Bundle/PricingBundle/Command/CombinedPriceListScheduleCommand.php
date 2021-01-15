<?php
declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prepares and activates combined price lists based on their schedules.
 */
class CombinedPriceListScheduleCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:price-lists:schedule';

    private ManagerRegistry $registry;
    private ConfigManager $configManager;
    private CombinedPriceListScheduleResolver $priceListResolver;
    private CombinedPriceListTriggerHandler $triggerHandler;
    private CombinedPriceListsBuilderFacade $builder;

    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        CombinedPriceListScheduleResolver $priceListResolver,
        CombinedPriceListTriggerHandler $triggerHandler,
        CombinedPriceListsBuilderFacade $builder
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->priceListResolver = $priceListResolver;
        $this->triggerHandler = $triggerHandler;
        $this->builder = $builder;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Prepares and activates combined price lists based on their schedules.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command prepares and activates combined price lists
based on their schedules.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    public function isActive()
    {
        $offsetHours = $this->configManager->get('oro_pricing.offset_of_processing_cpl_prices');

        $count = $this->registry
            ->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class)
            ->getCPLsForPriceCollectByTimeOffsetCount($offsetHours);

        return ($count > 0);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->triggerHandler->startCollect();

        // Build not calculated CPLs before switch
        $this->combinePricesForScheduledCPL();
        // Switch to scheduled CPLs according to activation schedule
        $this->priceListResolver->updateRelations();

        $this->triggerHandler->commit();
    }

    protected function combinePricesForScheduledCPL()
    {
        $offsetHours = $this->configManager->get('oro_pricing.offset_of_processing_cpl_prices');

        $combinedPriceLists = $this->registry
            ->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class)
            ->getCPLsForPriceCollectByTimeOffset($offsetHours);

        $this->builder->rebuild($combinedPriceLists);
        $this->builder->dispatchEvents();
    }

    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }
}
