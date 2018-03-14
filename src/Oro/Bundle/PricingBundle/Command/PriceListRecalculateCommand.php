<?php

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerGroupRepository;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectExecutorAwareInterface;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PriceListRecalculateCommand extends ContainerAwareCommand
{
    const NAME = 'oro:price-lists:recalculate';
    const ALL = 'all';
    const ACCOUNT = 'customer';
    const ACCOUNT_GROUP = 'customer-group';
    const WEBSITE = 'website';
    const PRICE_LIST = 'price-list';
    const DISABLE_TRIGGERS = 'disable-triggers';
    const VERBOSE = 'verbose';
    const USE_INSERT_SELECT = 'use-insert-select';
    const INCLUDE_DEPENDENT = 'include-dependent';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addOption(self::ALL)
            ->addOption(
                self::ACCOUNT,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'customer ids for prices recalculate',
                []
            )
            ->addOption(
                self::ACCOUNT_GROUP,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'customer group ids for prices recalculate',
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
            ->addOption(
                self::INCLUDE_DEPENDENT,
                null,
                InputOption::VALUE_NONE,
                sprintf('recalculate prices for dependent price lists included in the %s option', self::PRICE_LIST)
            )
            ->addOption(
                self::DISABLE_TRIGGERS,
                null,
                InputOption::VALUE_NONE,
                'disables ALL triggers before the operation, allowing faster reindexation of bigger data'
            )
            ->addOption(
                self::USE_INSERT_SELECT,
                null,
                InputOption::VALUE_NONE,
                'Use INSERT SELECT queries instead multi INSERTS'
            )
            ->setDescription('Recalculate combined price list and combined product prices');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('oro_pricing.pricing_strategy.strategy_register')
            ->getCurrentStrategy()
            ->setOutput($output);

        $disableTriggers = (bool)$input->getOption(self::DISABLE_TRIGGERS);
        if (true === $disableTriggers) {
            $this->disableAllTriggers($output);
        }
        $this->prepareBuilders($input);
        $optionAll = (bool)$input->getOption(self::ALL);
        if ($optionAll) {
            $this->processAllPriceLists($output);
        } elseif ($input->getOption(self::PRICE_LIST)) {
            $this->processPriceLists($input, $output);
        } elseif ($input->getOption(self::WEBSITE)
            || $input->getOption(self::ACCOUNT)
            || $input->getOption(self::ACCOUNT_GROUP)
        ) {
            $this->processCombinedPriceLists($input, $output);
        } else {
            $output->writeln(
                '<comment>ATTENTION</comment>: To update all price lists run command with <info>--all</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --all</info>', $this->getName()));
        }
        if (true === $disableTriggers) {
            $this->enableAllTriggers($output);
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function processAllPriceLists(OutputInterface $output)
    {
        $output->writeln('<info>Start processing of all Price rules</info>');
        $this->buildPriceRulesForAllPriceLists();

        $output->writeln('<info>Start combining all Price Lists</info>');
        $builder = $this->getContainer()->get('oro_pricing.builder.combined_price_list_builder_facade');
        $builder->rebuildAll(time());
        $builder->dispatchEvents();
        $output->writeln('<info>The cache is updated successfully</info>');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function processPriceLists(InputInterface $input, OutputInterface $output)
    {
        $priceLists = $this->getPriceLists($input);

        $output->writeln('<info>Start the process Price rules</info>');
        $this->buildPriceRulesByPriceLists($priceLists);

        $output->writeln('<info>Start combining Price Lists</info>');
        $this->buildCombinedPriceListsByPriceLists($priceLists);
        $output->writeln('<info>The cache is updated successfully</info>');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function processCombinedPriceLists(InputInterface $input, OutputInterface $output)
    {
        // Price list chains for given parameters may contain any set of price lists with duplication
        // To get actual prices all price rules should be actualized
        $output->writeln('<info>Start processing of all Price rules</info>');
        $this->buildPriceRulesForAllPriceLists();

        $output->writeln('<info>Start combining Price Lists</info>');

        $websites = $this->getWebsites($input);
        $customerGroups = $this->getCustomerGroups($input);
        $customers = $this->getCustomers($input);

        $container = $this->getContainer();
        $databaseTriggerManager = $container->get('oro_pricing.database_triggers.manager.combined_prices');
        $builder = $this->getContainer()->get('oro_pricing.builder.combined_price_list_builder_facade');

        $now = time();
        if (!$customerGroups && !$customers) {
            $builder->rebuildForWebsites($websites, $now);
        } else {
            foreach ($websites as $website) {
                if ($customerGroups) {
                    $builder->rebuildForCustomerGroups($customerGroups, $website, $now);
                }
                if ($customers) {
                    $builder->rebuildForCustomers($customers, $website, $now);
                }
            }
        }
        $builder->dispatchEvents();
        $output->writeln('<info>Enabling triggers for the CPL table</info>');
        $databaseTriggerManager->enable();
        $output->writeln('<info>The cache is updated successfully</info>');
    }

    /**
     * @param InputInterface $input
     * @return PriceList[]
     */
    protected function getPriceLists(InputInterface $input)
    {
        $priceListIds = $input->getOption(self::PRICE_LIST);
        $registry = $this->getContainer()->get('doctrine');
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $registry
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);

        /** @var PriceList[] $priceLists */
        $priceLists = $priceListRepository->findBy(['id' => $priceListIds]);

        if ((bool)$input->getOption(self::INCLUDE_DEPENDENT)) {
            $priceListsWithDependent = $priceLists;

            foreach ($priceLists as $priceList) {
                $priceListsWithDependent = array_merge(
                    $priceListsWithDependent,
                    $this->getDependentPriceLists($priceList)
                );
            }

            return $priceListsWithDependent;
        }

        return $priceLists;
    }

    /**
     * @param PriceList[]|int[] $priceLists
     */
    protected function buildPriceRulesByPriceLists($priceLists)
    {
        /** @var ProductPriceBuilder $priceBuilder */
        $priceBuilder = $this->getContainer()->get('oro_pricing.builder.product_price_builder');
        /** @var PriceListProductAssignmentBuilder $assignmentBuilder */
        $assignmentBuilder = $this->getContainer()
            ->get('oro_pricing.builder.price_list_product_assignment_builder');

        foreach ($priceLists as $priceList) {
            $assignmentBuilder->buildByPriceListWithoutEventDispatch($priceList);
            $priceBuilder->buildByPriceListWithoutTriggers($priceList);
        }
    }

    /**
     * @param PriceList[]|int[] $priceLists
     */
    protected function buildCombinedPriceListsByPriceLists($priceLists)
    {
        $builder = $this->getContainer()->get('oro_pricing.builder.combined_price_list_builder_facade');
        $builder->rebuildForPriceLists($priceLists, time());
        $builder->dispatchEvents();
    }

    protected function buildPriceRulesForAllPriceLists()
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $registry
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);
        $priceLists = $priceListRepository->getPriceListsWithRules();
        $this->buildPriceRulesByPriceLists($priceLists);
    }

    /**
     * @param InputInterface $input
     * @return array|Website[]
     */
    protected function getWebsites(InputInterface $input)
    {
        $websiteIds = $input->getOption(self::WEBSITE);
        /** @var WebsiteRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Website::class)
            ->getRepository(Website::class);
        if (count($websiteIds) === 0) {
            $websites = $repository->findAll();
        } else {
            $websites = $repository->findBy(['id' => $websiteIds]);
        }

        return $websites;
    }

    /**
     * @param InputInterface $input
     * @return array|CustomerGroup[]
     */
    protected function getCustomerGroups(InputInterface $input)
    {
        $customerGroupIds = $input->getOption(self::ACCOUNT_GROUP);
        /** @var CustomerGroupRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CustomerGroup::class)
            ->getRepository(CustomerGroup::class);
        $customerGroups = [];
        if (count($customerGroupIds)) {
            $customerGroups = $repository->findBy(['id' => $customerGroupIds]);
        }

        return $customerGroups;
    }

    /**
     * @param InputInterface $input
     * @return array|Customer[]
     */
    protected function getCustomers(InputInterface $input)
    {
        $customerIds = $input->getOption(self::ACCOUNT);
        /** @var CustomerRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Customer::class)
            ->getRepository(Customer::class);
        $customers = [];
        if (count($customerIds)) {
            $customers = $repository->findBy(['id' => $customerIds]);
        }

        return $customers;
    }

    /**
     * @param PriceList $priceList
     * @return PriceList[]
     */
    protected function getDependentPriceLists(PriceList $priceList)
    {
        /** @var PriceRuleLexeme[] $lexemes */
        $lexemes = $this->getContainer()->get('oro_pricing.price_rule_lexeme_trigger_handler')->findEntityLexemes(
            PriceList::class,
            [],
            $priceList->getId()
        );

        $priceLists = [];
        if (count($lexemes) > 0) {
            $dependentPriceLists = [];
            foreach ($lexemes as $lexeme) {
                $dependentPriceList = $lexeme->getPriceList();
                $dependentPriceLists[$dependentPriceList->getId()] = $dependentPriceList;
            }

            $priceLists = $dependentPriceLists;
            foreach ($dependentPriceLists as $dependentPriceList) {
                $priceLists = array_merge($priceLists, $this->getDependentPriceLists($dependentPriceList));
            }
        }

        return $priceLists;
    }

    /**
     * @param OutputInterface $output
     */
    protected function disableAllTriggers(OutputInterface $output)
    {
        $output->writeln('<info>Disabling ALL triggers for the CPL table</info>');

        $container              = $this->getContainer();
        $databaseTriggerManager = $container->get('oro_pricing.database_triggers.manager.combined_prices');
        $databaseTriggerManager->disable();
    }

    /**
     * @param OutputInterface $output
     */
    protected function enableAllTriggers(OutputInterface $output)
    {
        $output->writeln('<info>Enabling ALL triggers for the CPL table</info>');

        $container              = $this->getContainer();
        $databaseTriggerManager = $container->get('oro_pricing.database_triggers.manager.combined_prices');
        $databaseTriggerManager->enable();
    }

    /**
     * @param InputInterface $input
     */
    private function prepareBuilders(InputInterface $input)
    {
        $container = $this->getContainer();
        $currentStrategy = $container->get('oro_pricing.pricing_strategy.strategy_register')->getCurrentStrategy();
        if ($input->getOption(self::USE_INSERT_SELECT)
            && $currentStrategy instanceof InsertFromSelectExecutorAwareInterface
        ) {
            $queryExecutor = $container->get('oro_pricing.orm.insert_from_select_query_executor');
            $currentStrategy->setInsertSelectExecutor($queryExecutor);
        }
    }
}
