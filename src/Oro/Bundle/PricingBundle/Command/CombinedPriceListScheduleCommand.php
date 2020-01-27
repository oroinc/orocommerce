<?php

namespace Oro\Bundle\PricingBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
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
 * Prepares and activates combined price list by schedule
 */
class CombinedPriceListScheduleCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:price-lists:schedule';

    /** @var ManagerRegistry */
    private $registry;

    /** @var ConfigManager */
    private $configManager;

    /** @var CombinedPriceListScheduleResolver */
    private $priceListResolver;

    /** @var CombinedPriceListTriggerHandler */
    private $triggerHandler;

    /** @var CombinedPriceListsBuilderFacade */
    private $builder;

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     * @param CombinedPriceListScheduleResolver $priceListResolver
     * @param CombinedPriceListTriggerHandler $triggerHandler
     * @param CombinedPriceListsBuilderFacade $builder
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Prepare and activate combined price list by schedule');
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
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

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }
}
