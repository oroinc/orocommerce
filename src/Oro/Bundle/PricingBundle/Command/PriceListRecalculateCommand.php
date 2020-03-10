<?php

namespace Oro\Bundle\PricingBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerGroupRepository;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\EntityBundle\Manager\Db\EntityTriggerManager;
use Oro\Bundle\EntityBundle\ORM\InsertQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectExecutorAwareInterface;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Recalculate combined price list and combined product prices
 */
class PriceListRecalculateCommand extends Command
{
    const ALL = 'all';
    const ACCOUNT = 'customer';
    const ACCOUNT_GROUP = 'customer-group';
    const WEBSITE = 'website';
    const PRICE_LIST = 'price-list';
    const DISABLE_TRIGGERS = 'disable-triggers';
    const VERBOSE = 'verbose';
    const USE_INSERT_SELECT = 'use-insert-select';
    const INCLUDE_DEPENDENT = 'include-dependent';

    /** @var string */
    protected static $defaultName = 'oro:price-lists:recalculate';

    /** @var ManagerRegistry */
    private $registry;

    /** @var ProductPriceBuilder */
    private $priceBuilder;

    /** @var InsertQueryExecutorInterface */
    private $insertQueryExecutorInsertSelectMode;

    /** @var ShardQueryExecutorInterface */
    private $shardInsertQueryExecutorInsertSelectMode;

    /** @var InsertQueryExecutorInterface */
    private $insertQueryExecutorMultiInsertMode;

    /** @var ShardQueryExecutorInterface */
    private $shardInsertQueryExecutorMultiInsertMode;

    /** @var DependentPriceListProvider */
    private $dependentPriceListProvider;

    /** @var CombinedPriceListTriggerHandler */
    private $triggerHandler;

    /** @var StrategyRegister */
    private $strategyRegister;

    /** @var CombinedPriceListsBuilderFacade */
    private $builder;

    /** @var EntityTriggerManager */
    private $databaseTriggerManager;

    /** @var PriceListProductAssignmentBuilder */
    private $assignmentBuilder;

    /**
     * @param ManagerRegistry $registry
     * @param ProductPriceBuilder $priceBuilder
     * @param DependentPriceListProvider $dependentPriceListProvider
     * @param CombinedPriceListTriggerHandler $triggerHandler
     * @param StrategyRegister $strategyRegister
     * @param CombinedPriceListsBuilderFacade $builder
     * @param EntityTriggerManager $databaseTriggerManager
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     */
    public function __construct(
        ManagerRegistry $registry,
        ProductPriceBuilder $priceBuilder,
        DependentPriceListProvider $dependentPriceListProvider,
        CombinedPriceListTriggerHandler $triggerHandler,
        StrategyRegister $strategyRegister,
        CombinedPriceListsBuilderFacade $builder,
        EntityTriggerManager $databaseTriggerManager,
        PriceListProductAssignmentBuilder $assignmentBuilder
    ) {
        $this->registry = $registry;
        $this->priceBuilder = $priceBuilder;
        $this->dependentPriceListProvider = $dependentPriceListProvider;
        $this->triggerHandler = $triggerHandler;
        $this->strategyRegister = $strategyRegister;
        $this->builder = $builder;
        $this->databaseTriggerManager = $databaseTriggerManager;
        $this->assignmentBuilder = $assignmentBuilder;

        parent::__construct();
    }

    /**
     * @param InsertQueryExecutorInterface $insertQueryExecutor
     * @param ShardQueryExecutorInterface  $shardInsertQueryExecutor
     * @param bool                         $applyOnInsertSelectMode
     */
    public function setInsertQueryExecutors(
        InsertQueryExecutorInterface $insertQueryExecutor,
        ShardQueryExecutorInterface $shardInsertQueryExecutor,
        bool $applyOnInsertSelectMode
    ) {
        if ($applyOnInsertSelectMode) {
            $this->insertQueryExecutorInsertSelectMode = $insertQueryExecutor;
            $this->shardInsertQueryExecutorInsertSelectMode = $shardInsertQueryExecutor;
        } else {
            $this->insertQueryExecutorMultiInsertMode = $insertQueryExecutor;
            $this->shardInsertQueryExecutorMultiInsertMode = $shardInsertQueryExecutor;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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
                'disables ALL triggers before the operation, allowing faster reindexation of bigger data.
                Not available for MySQL or MySQL-based database platforms'
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
        /** @var CombinedPriceListTriggerHandler $triggerHandler */
        $this->triggerHandler->startCollect();

        $this->strategyRegister
            ->getCurrentStrategy()
            ->setOutput($output);

        $disableTriggers = (bool)$input->getOption(self::DISABLE_TRIGGERS);

        if (true === $disableTriggers) {
            $databasePlatform = $this->registry->getConnection()->getDatabasePlatform();
            if ($databasePlatform instanceof MySqlPlatform) {
                throw new \InvalidArgumentException(sprintf(
                    'The option `%s` is not available for `%s` database platform.',
                    self::DISABLE_TRIGGERS,
                    $databasePlatform->getName()
                ));
            }

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

        $this->triggerHandler->commit();
    }

    /**
     * @param OutputInterface $output
     */
    protected function processAllPriceLists(OutputInterface $output)
    {
        $output->writeln('<info>Start processing of all Price rules</info>');
        $this->buildPriceRulesForAllPriceLists();

        $output->writeln('<info>Start combining all Price Lists</info>');
        $this->builder->rebuildAll(time());
        $this->builder->dispatchEvents();
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

        $now = time();
        if (!$customerGroups && !$customers) {
            $this->builder->rebuildForWebsites($websites, $now);
        } else {
            foreach ($websites as $website) {
                if ($customerGroups) {
                    $this->builder->rebuildForCustomerGroups($customerGroups, $website, $now);
                }
                if ($customers) {
                    $this->builder->rebuildForCustomers($customers, $website, $now);
                }
            }
        }
        $this->builder->dispatchEvents();
        $output->writeln('<info>Enabling triggers for the CPL table</info>');
        $this->databaseTriggerManager->enable();
        $output->writeln('<info>The cache is updated successfully</info>');
    }

    /**
     * @param InputInterface $input
     * @return PriceList[]
     */
    protected function getPriceLists(InputInterface $input)
    {
        $priceListIds = $input->getOption(self::PRICE_LIST);
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->registry
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);

        /** @var PriceList[] $priceLists */
        $priceLists = $priceListRepository->findBy(['id' => $priceListIds]);

        if (!$input->getOption(self::INCLUDE_DEPENDENT)) {
            return $priceLists;
        }

        return $this->dependentPriceListProvider->appendDependent($priceLists);
    }

    /**
     * @param PriceList[]|int[] $priceLists
     */
    protected function buildPriceRulesByPriceLists($priceLists)
    {
        foreach ($priceLists as $priceList) {
            $this->assignmentBuilder->buildByPriceListWithoutEventDispatch($priceList);
            $this->priceBuilder->buildByPriceListWithoutTriggers($priceList);
        }
    }

    /**
     * @param PriceList[]|int[] $priceLists
     */
    protected function buildCombinedPriceListsByPriceLists($priceLists)
    {
        $this->builder->rebuildForPriceLists($priceLists, time());
        $this->builder->dispatchEvents();
    }

    protected function buildPriceRulesForAllPriceLists()
    {
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->registry
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
        $repository = $this->registry
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
        $repository = $this->registry
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
        $repository = $this->registry
            ->getManagerForClass(Customer::class)
            ->getRepository(Customer::class);
        $customers = [];
        if (count($customerIds)) {
            $customers = $repository->findBy(['id' => $customerIds]);
        }

        return $customers;
    }

    /**
     * @param OutputInterface $output
     */
    protected function disableAllTriggers(OutputInterface $output)
    {
        $output->writeln('<info>Disabling ALL triggers for the CPL table</info>');
        $this->databaseTriggerManager->disable();
    }

    /**
     * @param OutputInterface $output
     */
    protected function enableAllTriggers(OutputInterface $output)
    {
        $output->writeln('<info>Enabling ALL triggers for the CPL table</info>');
        $this->databaseTriggerManager->enable();
    }

    /**
     * @param InputInterface $input
     */
    private function prepareBuilders(InputInterface $input)
    {
        $currentStrategy = $this->strategyRegister->getCurrentStrategy();
        if ($input->getOption(self::USE_INSERT_SELECT)
            && $currentStrategy instanceof InsertFromSelectExecutorAwareInterface
        ) {
            $currentStrategy->setInsertSelectExecutor($this->shardInsertQueryExecutorInsertSelectMode);
        }

        if ($input->getOption(self::USE_INSERT_SELECT)) {
            $this->assignmentBuilder->setInsertQueryExecutor($this->insertQueryExecutorInsertSelectMode);
            $this->priceBuilder->setShardInsertQueryExecutor($this->shardInsertQueryExecutorInsertSelectMode);
        } else {
            $this->assignmentBuilder->setInsertQueryExecutor($this->insertQueryExecutorMultiInsertMode);
            $this->priceBuilder->setShardInsertQueryExecutor($this->shardInsertQueryExecutorMultiInsertMode);
        }
    }
}
