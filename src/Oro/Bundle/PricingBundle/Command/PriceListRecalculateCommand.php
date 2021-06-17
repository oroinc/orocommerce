<?php
declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Command;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\Persistence\ManagerRegistry;
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
 * Recalculates combined price lists and product prices.
 */
class PriceListRecalculateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:price-lists:recalculate';

    private ManagerRegistry $registry;
    private ProductPriceBuilder $priceBuilder;
    private InsertQueryExecutorInterface $insertQueryExecutorInsertSelectMode;
    private ShardQueryExecutorInterface $shardInsertQueryExecutorInsertSelectMode;
    private InsertQueryExecutorInterface $insertQueryExecutorMultiInsertMode;
    private ShardQueryExecutorInterface $shardInsertQueryExecutorMultiInsertMode;
    private DependentPriceListProvider $dependentPriceListProvider;
    private CombinedPriceListTriggerHandler $triggerHandler;
    private StrategyRegister $strategyRegister;
    private CombinedPriceListsBuilderFacade $builder;
    private EntityTriggerManager $databaseTriggerManager;
    private PriceListProductAssignmentBuilder $assignmentBuilder;

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

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('all')
            ->addOption(
                'customer',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Customer IDs',
                []
            )
            ->addOption(
                'customer-group',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Customer group IDs',
                []
            )
            ->addOption(
                'website',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Website IDs',
                []
            )
            ->addOption(
                'price-list',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Price list IDs',
                []
            )
            ->addOption(
                'include-dependent',
                null,
                InputOption::VALUE_NONE,
                'Recalculate prices for dependent price lists included in the price-list option'
            )
            ->addOption(
                'disable-triggers',
                null,
                InputOption::VALUE_NONE,
                'Disable ALL triggers to allow for faster calculations (not available for MySQL-based databases)'
            )
            ->addOption(
                'use-insert-select',
                null,
                InputOption::VALUE_NONE,
                'Use INSERT SELECT queries instead of multi INSERTS'
            )
            ->setDescription('Recalculates combined price lists and product prices.')
            ->setHelp(
                // @codingStandardsIgnoreStart
                <<<'HELP'
The <info>%command.name%</info> command recalculates combined price lists and product prices.

  <info>php %command.full_name%</info>

Use the <info>--customer</info>, <info>--customer-group</info> or <info>--website</info> options to recalculate only the prices
related to the specified customers, customer groups or websites:

  <info>php %command.full_name% --customer=<ID1> --customer=<ID2> --customer=<IDN></info>
  <info>php %command.full_name% --customer-group=<ID1> --customer-group=<ID2> --customer-group=<IDN></info>
  <info>php %command.full_name% --website=<ID1> --website=<ID2> --website=<IDN></info>

The <info>--price-list</info> option can limit the scope of the recalculations to the combined price lists
that are derived from the specified price lists:

  <info>php %command.full_name% --price-list=<ID1> --price-list=<ID2> --price-list=<IDN></info>
  
If the price calculation rules refer to other price lists, the <info>--include-dependent</info> option can be used
to propagate the changes to all affected price lists:

  <info>php %command.full_name% --include-dependent --price-list=<ID1> --price-list=<ID2> --price-list=<IDN></info>

This command can also be used with the <info>--all</info> option to recalculate all combined price lists in the system:

  <info>php %command.full_name% --all</info>

The two additional options <info>--disable-triggers</info> (not available in MySQL-based databases) and
<info>--use-insert-select</info> may help to speed up the calculations on large data sets.

  <info>php %command.full_name% --all --disable-triggers --use-insert-select</info>

HELP
                // @codingStandardsIgnoreEnd
            )
            ->addUsage('--customer=<ID1> --customer=<ID2> --customer=<IDN>')
            ->addUsage('--customer-group=<ID1> --customer-group=<ID2> --customer-group=<IDN>')
            ->addUsage('--website=<ID1> --website=<ID2> --website=<IDN>')
            ->addUsage('--price-list=<ID1> --price-list=<ID2> --price-list=<IDN>')
            ->addUsage('--include-dependent --price-list=<ID1> --price-list=<ID2> --price-list=<IDN>')
            ->addUsage('--all')
            ->addUsage('--all --disable-triggers --use-insert-select')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var CombinedPriceListTriggerHandler $triggerHandler */
        $this->triggerHandler->startCollect();

        $this->strategyRegister
            ->getCurrentStrategy()
            ->setOutput($output);

        $disableTriggers = (bool)$input->getOption('disable-triggers');

        if (true === $disableTriggers) {
            $databasePlatform = $this->registry->getConnection()->getDatabasePlatform();
            if ($databasePlatform instanceof MySqlPlatform) {
                throw new \InvalidArgumentException(sprintf(
                    'The option `%s` is not available for `%s` database platform.',
                    'disable-triggers',
                    $databasePlatform->getName()
                ));
            }

            $this->disableAllTriggers($output);
        }

        $this->prepareBuilders($input);
        $optionAll = (bool)$input->getOption('all');
        if ($optionAll) {
            $this->processAllPriceLists($output);
        } elseif ($input->getOption('price-list')) {
            $this->processPriceLists($input, $output);
        } elseif ($input->getOption('website')
            || $input->getOption('customer')
            || $input->getOption('customer-group')
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

    protected function processAllPriceLists(OutputInterface $output): void
    {
        $output->writeln('<info>Start processing of all Price rules</info>');
        $this->buildPriceRulesForAllPriceLists();

        $output->writeln('<info>Start combining all Price Lists</info>');
        $this->builder->rebuildAll(time());
        $this->builder->dispatchEvents();
        $output->writeln('<info>The cache is updated successfully</info>');
    }

    protected function processPriceLists(InputInterface $input, OutputInterface $output): void
    {
        $priceLists = $this->getPriceLists($input);

        $output->writeln('<info>Start the process Price rules</info>');
        $this->buildPriceRulesByPriceLists($priceLists);

        $output->writeln('<info>Start combining Price Lists</info>');
        $this->buildCombinedPriceListsByPriceLists($priceLists);
        $output->writeln('<info>The cache is updated successfully</info>');
    }

    protected function processCombinedPriceLists(InputInterface $input, OutputInterface $output): void
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
        $output->writeln('<info>The cache is updated successfully</info>');
    }

    /**
     * @return PriceList[]
     */
    protected function getPriceLists(InputInterface $input): array
    {
        $priceListIds = $input->getOption('price-list');
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->registry
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);

        /** @var PriceList[] $priceLists */
        $priceLists = $priceListRepository->findBy(['id' => $priceListIds]);

        if (!$input->getOption('include-dependent')) {
            return $priceLists;
        }

        return $this->dependentPriceListProvider->appendDependent($priceLists);
    }

    /**
     * @param PriceList[]|int[] $priceLists
     */
    protected function buildPriceRulesByPriceLists(iterable $priceLists): void
    {
        foreach ($priceLists as $priceList) {
            $this->assignmentBuilder->buildByPriceListWithoutEventDispatch($priceList);
            $this->priceBuilder->buildByPriceListWithoutTriggers($priceList);
        }
    }

    /**
     * @param PriceList[]|int[] $priceLists
     */
    protected function buildCombinedPriceListsByPriceLists(iterable $priceLists): void
    {
        $this->builder->rebuildForPriceLists($priceLists, time());
        $this->builder->dispatchEvents();
    }

    protected function buildPriceRulesForAllPriceLists(): void
    {
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->registry
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);
        $priceLists = $priceListRepository->getPriceListsWithRules();
        $this->buildPriceRulesByPriceLists($priceLists);
    }

    /**
     * @return array|Website[]
     */
    protected function getWebsites(InputInterface $input): array
    {
        $websiteIds = $input->getOption('website');
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
     * @return array|CustomerGroup[]
     */
    protected function getCustomerGroups(InputInterface $input): array
    {
        $customerGroupIds = $input->getOption('customer-group');
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
     * @return array|Customer[]
     */
    protected function getCustomers(InputInterface $input): array
    {
        $customerIds = $input->getOption('customer');
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

    protected function disableAllTriggers(OutputInterface $output): void
    {
        $output->writeln('<info>Disabling ALL triggers for the CPL table</info>');
        $this->databaseTriggerManager->disable();
    }

    protected function enableAllTriggers(OutputInterface $output): void
    {
        $output->writeln('<info>Enabling ALL triggers for the CPL table</info>');
        $this->databaseTriggerManager->enable();
    }

    private function prepareBuilders(InputInterface $input): void
    {
        $currentStrategy = $this->strategyRegister->getCurrentStrategy();
        if ($input->getOption('use-insert-select')
            && $currentStrategy instanceof InsertFromSelectExecutorAwareInterface
        ) {
            $currentStrategy->setInsertSelectExecutor($this->shardInsertQueryExecutorInsertSelectMode);
        }

        if ($input->getOption('use-insert-select')) {
            $this->assignmentBuilder->setInsertQueryExecutor($this->insertQueryExecutorInsertSelectMode);
            $this->priceBuilder->setShardInsertQueryExecutor($this->shardInsertQueryExecutorInsertSelectMode);
        } else {
            $this->assignmentBuilder->setInsertQueryExecutor($this->insertQueryExecutorMultiInsertMode);
            $this->priceBuilder->setShardInsertQueryExecutor($this->shardInsertQueryExecutorMultiInsertMode);
        }
    }
}
