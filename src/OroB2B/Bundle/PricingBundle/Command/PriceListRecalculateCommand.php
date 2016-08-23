<?php

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Repository\AccountGroupRepository;
use Oro\Bundle\AccountBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
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
    const ACCOUNT = 'account';
    const ACCOUNT_GROUP = 'account-group';
    const WEBSITE = 'website';
    const PRICE_LIST = 'price-list';

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
    }

    /**
     * @param OutputInterface $output
     */
    protected function processAllPriceLists(OutputInterface $output)
    {
        $output->writeln('<info>Start processing of all Price rules</info>');
        $this->buildPriceRulesForAllPriceLists();

        $output->writeln('<info>Start combining all Price Lists</info>');
        $this->getContainer()->get('orob2b_pricing.builder.combined_price_list_builder')->build(true);
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
        $accountGroups = $this->getAccountGroups($input);
        $accounts = $this->getAccounts($input);

        $container = $this->getContainer();
        $websiteCPLBuilder = $container->get('orob2b_pricing.builder.website_combined_price_list_builder');
        $accountGroupCPLBuilder = $container->get('orob2b_pricing.builder.account_group_combined_price_list_builder');
        $accountCPLBuilder = $container->get('orob2b_pricing.builder.account_combined_price_list_builder');

        foreach ($websites as $website) {
            if (count($accountGroups) === 0 && count($accounts) === 0) {
                $websiteCPLBuilder->build($website, true);
            } else {
                foreach ($accountGroups as $accountGroup) {
                    $accountGroupCPLBuilder->build($website, $accountGroup, true);
                }
                foreach ($accounts as $account) {
                    $accountCPLBuilder->build($website, $account, true);
                }
            }
        }

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
        return $priceListRepository->findBy(['id' => $priceListIds]);
    }

    /**
     * @param PriceList[]|int[] $priceLists
     */
    protected function buildPriceRulesByPriceLists($priceLists)
    {
        /** @var ProductPriceBuilder $priceBuilder */
        $priceBuilder = $this->getContainer()->get('orob2b_pricing.builder.product_price_builder');
        /** @var PriceListProductAssignmentBuilder $assignmentBuilder */
        $assignmentBuilder = $this->getContainer()
            ->get('orob2b_pricing.builder.price_list_product_assignment_builder');

        foreach ($priceLists as $priceList) {
            $assignmentBuilder->buildByPriceList($priceList);
            $priceBuilder->buildByPriceList($priceList);
        }
    }

    /**
     * @param PriceList[]|int[] $priceLists
     */
    protected function buildCombinedPriceListsByPriceLists($priceLists)
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var CombinedPriceListRepository $cplRepository */
        $cplRepository = $registry->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class);

        $cplIterator = $cplRepository->getCombinedPriceListsByPriceLists($priceLists);

        $priceResolver = $this->getContainer()->get('orob2b_pricing.resolver.combined_product_price_resolver');
        foreach ($cplIterator as $cpl) {
            $priceResolver->combinePrices($cpl);
        }
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
     * @return array|AccountGroup[]
     */
    protected function getAccountGroups(InputInterface $input)
    {
        $accountGroupIds = $input->getOption(self::ACCOUNT_GROUP);
        /** @var AccountGroupRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(AccountGroup::class)
            ->getRepository(AccountGroup::class);
        $accountGroups = [];
        if (count($accountGroupIds)) {
            $accountGroups = $repository->findBy(['id' => $accountGroupIds]);
        }

        return $accountGroups;
    }

    /**
     * @param InputInterface $input
     * @return array|Account[]
     */
    protected function getAccounts(InputInterface $input)
    {
        $accountIds = $input->getOption(self::ACCOUNT);
        /** @var AccountRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Account::class)
            ->getRepository(Account::class);
        $accounts = [];
        if (count($accountIds)) {
            $accounts = $repository->findBy(['id' => $accountIds]);
        }

        return $accounts;
    }
}
