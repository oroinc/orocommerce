<?php

namespace OroB2B\Bundle\PricingBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

class CombinedPriceListRecalculateCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:price-lists:recalculate';
    const FORCE = 'force';
    const ACCOUNT = 'account';
    const ACCOUNT_GROUP = 'account-group';
    const WEBSITE = 'website';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addOption(self::FORCE)
            ->addOption(
                self::ACCOUNT,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'account ids for prices recalculate',
                []
            )
            ->addOption(
                self::ACCOUNT_GROUP,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'account group ids for prices recalculate',
                []
            )
            ->addOption(
                self::WEBSITE,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'website ids for prices recalculate',
                []
            )
            ->setDescription('Recalculate combined price list and combined product prices');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = (bool)$input->getOption(self::FORCE);

        $container = $this->getContainer();
        $websiteIds = $input->getOption(self::WEBSITE);
        $accountGroupIds = $input->getOption(self::ACCOUNT_GROUP);
        $accountIds = $input->getOption(self::ACCOUNT);

        if (($websiteIds || $accountGroupIds || $accountIds) && !$force) {
            $output->writeln(
                '<comment>ATTENTION</comment>: To force execution run command with <info>--force</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --force</info>', $this->getName()));

            return;
        }

        if ($force) {
            $container->get('orob2b_pricing.recalculate_triggers_filler.scope_recalculate_triggers_filler')
                ->fillTriggersForRecalculate($websiteIds, $accountGroupIds, $accountIds);
        }

        $key = Configuration::getConfigKeyByName(Configuration::PRICE_LISTS_UPDATE_MODE);
        $mode = $container->get('oro_config.manager')->get($key);

        if ($force || $mode === CombinedPriceListQueueConsumer::MODE_SCHEDULED) {
            $output->writeln('<info>Start the process recalculation</info>');
            /** @var CombinedPriceListQueueConsumer $consumer */
            $priceListCollectionConsumer = $container->get('orob2b_pricing.builder.queue_consumer');
            $priceListCollectionConsumer->process();
            $productPriceConsumer = $container->get('orob2b_pricing.builder.combined_product_price_queue_consumer');
            $productPriceConsumer->process();

            $output->writeln('<info>The cache is updated successfully</info>');
        } else {
            $output->writeln('<info>Recalculation is not required, another mode is active</info>');
        }

        $this->calculatePricesForScheduleCPLs();
    }

    protected function calculatePricesForScheduleCPLs()
    {
        $offsetHours = $this->getContainer()->get('oro_config.manager')
            ->get('oro_b2b_pricing.offset_of_processing_cpl_prices');

        $combinedPriceLists = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:CombinedPriceList')
            ->getRepository('OroB2BPricingBundle:CombinedPriceList')
            ->getCPLsForPriceCollectByTimeOffset($offsetHours);

        $combinedProductPriceResolver = $this->getContainer()
            ->get('orob2b_pricing.resolver.combined_product_price_resolver');

        foreach ($combinedPriceLists as $combinedPriceList) {
            $combinedProductPriceResolver->combinePrices($combinedPriceList);
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
