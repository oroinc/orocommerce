<?php

namespace Oro\Bundle\PricingBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Builder\PriceRuleQueueConsumer;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleChangeTriggerRepository;

class CombinedPriceListRecalculateCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:price-lists:recalculate';
    const FORCE = 'force';
    const ACCOUNT = 'account';
    const ACCOUNT_GROUP = 'account-group';
    const WEBSITE = 'website';
    const PRICE_LIST = 'pricelist';

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
            ->addOption(
                self::PRICE_LIST,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'price list ids for prices recalculate',
                []
            )
            ->setDescription('Recalculate combined price list and combined product prices');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = Configuration::getConfigKeyByName(Configuration::PRICE_LISTS_UPDATE_MODE);
        $container = $this->getContainer();
        $mode = $container->get('oro_config.manager')->get($key);

        $force = (bool)$input->getOption(self::FORCE);
        if ($force || $mode === CombinedPriceListQueueConsumer::MODE_SCHEDULED) {
            $this->processPriceRules($input, $output);
            $this->processCombinedPriceLists($input, $output);
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function processCombinedPriceLists(InputInterface $input, OutputInterface $output)
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
            $container->get('orob2b_pricing.triggers_filler.scope_recalculate_triggers_filler')
                ->fillTriggersForRecalculate($websiteIds, $accountGroupIds, $accountIds);
        }

        $output->writeln('<info>Start the process recalculation</info>');
        /** @var CombinedPriceListQueueConsumer $consumer */
        $priceListCollectionConsumer = $container->get('orob2b_pricing.builder.queue_consumer');
        $priceListCollectionConsumer->process();
        $productPriceConsumer = $container->get('orob2b_pricing.builder.combined_product_price_queue_consumer');
        $productPriceConsumer->process();

        $output->writeln('<info>The cache is updated successfully</info>');

        $this->calculatePricesForScheduleCPLs();
    }

    protected function calculatePricesForScheduleCPLs()
    {
        $offsetHours = $this->getContainer()->get('oro_config.manager')
            ->get('oro_b2b_pricing.offset_of_processing_cpl_prices');

        $combinedPriceLists = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:CombinedPriceList')
            ->getRepository('OroPricingBundle:CombinedPriceList')
            ->getCPLsForPriceCollectByTimeOffset($offsetHours);

        $combinedProductPriceResolver = $this->getContainer()
            ->get('orob2b_pricing.resolver.combined_product_price_resolver');

        foreach ($combinedPriceLists as $combinedPriceList) {
            $combinedProductPriceResolver->combinePrices($combinedPriceList);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function processPriceRules(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Start the process Price rules</info>');

        $priceListIds = $input->getOption(self::PRICE_LIST);
        $force = (bool)$input->getOption(self::FORCE);

        if ($priceListIds && !$force) {
            $output->writeln(
                '<comment>ATTENTION</comment>: To force execution run command with <info>--force</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --force</info>', $this->getName()));

            return;
        }

        if ($force) {
            /** @var PriceListRepository $priceListRepository */
            $priceListRepository = $this->getContainer()->get('doctrine')
                ->getManagerForClass(PriceList::class)
                ->getRepository(PriceList::class);

            if (!$priceListIds) {
                /** @var PriceRuleChangeTriggerRepository $triggerRepository */
                $triggerRepository = $this->getContainer()->get('doctrine')
                    ->getManagerForClass(PriceRuleChangeTrigger::class)
                    ->getRepository(PriceRuleChangeTrigger::class);

                $triggerRepository->deleteAll();
                $priceListIterator = $priceListRepository->getPriceListsWithRules();
            } else {
                $priceListIterator = $priceListRepository->findBy(['id' => $priceListIds]);
            }

            /** @var ProductPriceBuilder $builer */
            $priceBuilder = $this->getContainer()->get('orob2b_pricing.builder.product_price_builder');
            /** @var PriceListProductAssignmentBuilder $assignmentBuilder */
            $assignmentBuilder = $this->getContainer()
                ->get('orob2b_pricing.builder.price_list_product_assignment_builder');

            foreach ($priceListIterator as $priceList) {
                $assignmentBuilder->buildByPriceList($priceList);
                $priceBuilder->buildByPriceList($priceList);
            }
        } else {
            /** @var PriceRuleQueueConsumer $consumer */
            $consumer = $this->getContainer()->get('orob2b_pricing.builder.price_rule_queue_consumer');
            $consumer->process();
        }
    }
}
