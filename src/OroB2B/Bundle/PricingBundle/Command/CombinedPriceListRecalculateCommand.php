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
    const NAME = 'orob2b:pricing:combined_price_list_recalculate';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Recalculate combined price list and combined product prices');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        /** @var CombinedPriceListQueueConsumer $consumer */
        $consumer = $container->get('orob2b_pricing.builder.queue_consumer');
        $mode = $container->get('oro_config.manager')->get('orob2b_pricing.' . Configuration::PRICE_LISTS_UPDATE_MODE);
        if ($mode === CombinedPriceListQueueConsumer::MODE_SCHEDULED) {
            $output->writeln('<info>Start the process recalculation</info>');
            $consumer->process();
            $output->writeln('<info>The cache is updated successfully</info>');
        } elseif ($mode === CombinedPriceListQueueConsumer::MODE_REAL_TIME) {
            $output->writeln('<info>Recalculation is not required for real time mode</info>');
        } else {
            $output->writeln('<error>Recalculation mode is not defined</error>');
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
