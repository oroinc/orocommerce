<?php

namespace OroB2B\Bundle\PricingBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;

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
            ->setDescription('Activate combined price list by schedule based on price lists');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $container->get('orob2b_pricing.resolver.combined_product_schedule_resolver')->updateRelations();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }
}
