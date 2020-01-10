<?php

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prepares and activates combined price list by schedule
 */
class CombinedPriceListScheduleCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:price-lists:schedule';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Prepare and activate combined price list by schedule');
    }

    public function isActive()
    {
        $container = $this->getContainer();
        $offsetHours = $container->get('oro_config.manager')
            ->get('oro_pricing.offset_of_processing_cpl_prices');

        $count = $container->get('doctrine')
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
        $container = $this->getContainer();
        $triggerHandler = $container->get('oro_pricing.model.combined_price_list_trigger_handler');
        $triggerHandler->startCollect();

        // Build not calculated CPLs before switch
        $this->combinePricesForScheduledCPL();
        // Switch to scheduled CPLs according to activation schedule
        $container->get('oro_pricing.resolver.combined_product_schedule_resolver')->updateRelations();

        $triggerHandler->commit();
    }

    protected function combinePricesForScheduledCPL()
    {
        $container = $this->getContainer();
        $offsetHours = $this->getContainer()->get('oro_config.manager')
            ->get('oro_pricing.offset_of_processing_cpl_prices');

        $combinedPriceLists = $container->get('doctrine')
            ->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class)
            ->getCPLsForPriceCollectByTimeOffset($offsetHours);

        $builder = $this->getContainer()->get('oro_pricing.builder.combined_price_list_builder_facade');
        $builder->rebuild($combinedPriceLists);
        $builder->dispatchEvents();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }
}
