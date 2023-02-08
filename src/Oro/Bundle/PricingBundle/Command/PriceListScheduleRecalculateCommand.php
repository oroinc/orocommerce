<?php
declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerGroupRepository;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Schedules re-calculation and re-combination of combined price lists and product prices.
 */
class PriceListScheduleRecalculateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:price-lists:schedule-recalculate';

    private ManagerRegistry $registry;
    private DependentPriceListProvider $dependentPriceListProvider;
    private CombinedPriceListRelationTriggerHandler $cplRelationTriggerHandler;

    public function __construct(
        ManagerRegistry $registry,
        DependentPriceListProvider $dependentPriceListProvider,
        CombinedPriceListRelationTriggerHandler $combinedPriceListScheduleRelationTriggerHandler
    ) {
        $this->registry = $registry;
        $this->dependentPriceListProvider = $dependentPriceListProvider;
        $this->cplRelationTriggerHandler = $combinedPriceListScheduleRelationTriggerHandler;
        parent::__construct();
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
HELP
                // @codingStandardsIgnoreEnd
            )
            ->addUsage('--customer=<ID1> --customer=<ID2> --customer=<IDN>')
            ->addUsage('--customer-group=<ID1> --customer-group=<ID2> --customer-group=<IDN>')
            ->addUsage('--website=<ID1> --website=<ID2> --website=<IDN>')
            ->addUsage('--price-list=<ID1> --price-list=<ID2> --price-list=<IDN>')
            ->addUsage('--include-dependent --price-list=<ID1> --price-list=<ID2> --price-list=<IDN>')
            ->addUsage('--all');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $optionAll = (bool)$input->getOption('all');
        $hasUpdates = false;
        if ($optionAll) {
            $hasUpdates = $this->processAllPriceLists($output);
        } elseif ($input->getOption('price-list')) {
            $hasUpdates = $this->processPriceLists($input, $output);
        } elseif ($input->getOption('website')
            || $input->getOption('customer')
            || $input->getOption('customer-group')
        ) {
            $hasUpdates = $this->processCombinedPriceLists($input, $output);
        } else {
            $output->writeln(
                '<comment>ATTENTION</comment>:' .
                ' To schedule update all price lists run the command with <info>--all</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --all</info>', $this->getName()));
        }

        if ($hasUpdates) {
            $output->writeln('<info>Updates were scheduled</info>');
        } else {
            $output->writeln('<info>No updates scheduled</info>');
        }

        return self::SUCCESS;
    }

    private function processAllPriceLists(OutputInterface $output): bool
    {
        $output->writeln('<info>Scheduling all Price Lists combining</info>');
        $this->cplRelationTriggerHandler->handleFullRebuild();

        return true;
    }

    private function processPriceLists(InputInterface $input, OutputInterface $output): bool
    {
        $priceLists = $this->getPriceLists($input);
        if (count($priceLists)) {
            $this->buildCombinedPriceListsByPriceLists($priceLists, $output);

            return true;
        }

        return false;
    }

    private function processCombinedPriceLists(InputInterface $input, OutputInterface $output): bool
    {
        $output->writeln('<info>Scheduling combining Price Lists</info>');

        $websites = $this->getWebsites($input);
        $websitesCount = count($websites);
        if (!$input->getOption('customer') && !$input->getOption('customer-group')) {
            foreach ($websites as $website) {
                $output->writeln(sprintf('    <info>for Website ID %d</info>', $website->getId()));
                $this->cplRelationTriggerHandler->handleWebsiteChange($website);
            }

            return $websitesCount > 0;
        }

        $customerGroups = $this->getCustomerGroups($input);
        $customers = $this->getCustomers($input);
        foreach ($websites as $website) {
            if ($customerGroups) {
                foreach ($customerGroups as $customerGroup) {
                    $output->writeln(sprintf(
                        '    <info>for Customer Group ID %d;Website ID %d</info>',
                        $customerGroup->getId(),
                        $website->getId()
                    ));
                    $this->cplRelationTriggerHandler->handleCustomerGroupChange($customerGroup, $website);
                }
            }
            if ($customers) {
                foreach ($customers as $customer) {
                    $output->writeln(sprintf(
                        '    <info>for Customer ID %d;Website ID %d</info>',
                        $customer->getId(),
                        $website->getId()
                    ));
                    $this->cplRelationTriggerHandler->handleCustomerChange($customer, $website);
                }
            }
        }

        return ($websitesCount * count($customerGroups)) + ($websitesCount * count($customers)) > 0;
    }

    private function buildCombinedPriceListsByPriceLists(iterable $priceLists, OutputInterface $output): void
    {
        $output->writeln('<info>Scheduling combining Price Lists</info>');
        foreach ($priceLists as $priceList) {
            $output->writeln(sprintf('    <info>by Price List ID %d</info>', $priceList->getId()));
            $this->cplRelationTriggerHandler->handlePriceListStatusChange($priceList);
        }
    }

    private function getPriceLists(InputInterface $input): array
    {
        $priceListIds = $input->getOption('price-list');
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->registry->getRepository(PriceList::class);

        /** @var PriceList[] $priceLists */
        $priceLists = $priceListRepository->findBy(['id' => $priceListIds]);

        if (!$input->getOption('include-dependent')) {
            return $priceLists;
        }

        return $this->dependentPriceListProvider->appendDependent($priceLists);
    }

    private function getWebsites(InputInterface $input): array
    {
        $websiteIds = $input->getOption('website');
        /** @var WebsiteRepository $repository */
        $repository = $this->registry->getRepository(Website::class);

        if (count($websiteIds) === 0) {
            $websites = $repository->findAll();
        } else {
            $websites = $repository->findBy(['id' => $websiteIds]);
        }

        return $websites;
    }

    private function getCustomerGroups(InputInterface $input): array
    {
        $customerGroupIds = $input->getOption('customer-group');
        /** @var CustomerGroupRepository $repository */
        $repository = $this->registry->getRepository(CustomerGroup::class);
        $customerGroups = [];
        if (count($customerGroupIds)) {
            $customerGroups = $repository->findBy(['id' => $customerGroupIds]);
        }

        return $customerGroups;
    }

    private function getCustomers(InputInterface $input): array
    {
        $customerIds = $input->getOption('customer');
        /** @var CustomerRepository $repository */
        $repository = $this->registry->getRepository(Customer::class);
        $customers = [];
        if (count($customerIds)) {
            $customers = $repository->findBy(['id' => $customerIds]);
        }

        return $customers;
    }
}
