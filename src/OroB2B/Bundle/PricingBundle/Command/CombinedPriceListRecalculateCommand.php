<?php

namespace OroB2B\Bundle\PricingBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

class CombinedPriceListRecalculateCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:price-lists:recalculate';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addOption('force')
            ->setDescription('Recalculate combined price list and combined product prices');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = false;
        if ($input->getOption('force')) {
            $force = true;
        }
        $container = $this->getContainer();
        /** @var CombinedPriceListQueueConsumer $consumer */
        $priceListCollectionConsumer = $container->get('orob2b_pricing.builder.queue_consumer');
        $productPriceConsumer = $container->get('orob2b_pricing.builder.combined_product_price_queue_consumer');
        $key = Configuration::getConfigKeyByName(Configuration::PRICE_LISTS_UPDATE_MODE);
        $mode = $container->get('oro_config.manager')->get($key);
        if ($mode === CombinedPriceListQueueConsumer::MODE_SCHEDULED) {
            $output->writeln('<info>Start the process recalculation</info>');
            $priceListCollectionConsumer->process($force);
            $productPriceConsumer->process();
            $output->writeln('<info>The cache is updated successfully</info>');
        } else {
            $output->writeln('<info>Recalculation is not required, another mode is active</info>');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }
}
