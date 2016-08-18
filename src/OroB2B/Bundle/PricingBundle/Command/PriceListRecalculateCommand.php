<?php

namespace OroB2B\Bundle\PricingBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use OroB2B\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

class PriceListRecalculateCommand extends ContainerAwareCommand
{
    const NAME = 'oro:price-lists:recalculate';
    const ALL = 'all';
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
        $this->processPriceRules($input, $output);
        $this->processCombinedPriceLists($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function processCombinedPriceLists(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $websiteIds = $input->getOption(self::WEBSITE);
        $accountGroupIds = $input->getOption(self::ACCOUNT_GROUP);
        $accountIds = $input->getOption(self::ACCOUNT);
        $output->writeln('<info>Start combining Price Lists</info>');

        $optionAll = $input->getOption(self::ALL);
        $runWithParameters = !empty($websiteIds) || !empty($accountGroupIds) || !empty($accountIds);
        if (!$runWithParameters && !$optionAll) {
            $output->writeln(
                '<comment>ATTENTION</comment>: To update all CPL\'s run command with <info>--all</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --all</info>', $this->getName()));

            return;
        }
        if ($optionAll) {
            $container->get('orob2b_pricing.builder.combined_price_list_builder')->build(true);
            return;
        }
        $this->buildCPLByParameters($input);
        $output->writeln('<info>The cache is updated successfully</info>');
    }

    /**
     * @param InputInterface $input
     */
    protected function buildCPLByParameters(InputInterface $input)
    {
        $container = $this->getContainer();
        $websites = $this->getWebsites($input);
        $accountGroups = $this->getAccountGroups($input);
        $accounts = $this->getAccounts($input);

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
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function processPriceRules(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Start the process Price rules</info>');

        $priceListIds = $input->getOption(self::PRICE_LIST);
        $optionAll = (bool)$input->getOption(self::ALL);

        if (!$priceListIds && !$optionAll) {
            $output->writeln(
                '<comment>ATTENTION</comment>: '
                . 'To process all price lists rules execution run command with <info>--all</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --all</info>', $this->getName()));

            return;
        }

        $registry = $this->getContainer()->get('doctrine');
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $registry
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);
        $priceListIterator = [];
        if ($priceListIds) {
            $priceListIterator = $priceListRepository->findBy(['id' => $priceListIds]);
        }
        if ($optionAll) {
            $priceListIterator = $priceListRepository->getPriceListsWithRules();
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

        $cplRepository = $registry->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class);

        if (count($priceListIds) === 0) {
            $cplIterator = [];
            // if parameter ALL is set, all CPL's will be processed anyway
            if (!$optionAll) {
                $cplIterator = new BufferedQueryResultIterator($cplRepository->createQueryBuilder('cpl'));
            }
        } else {
            $cplIterator = $cplRepository->getCombinedPriceListsByPriceLists($priceListIds);
        }

        $priceResolver = $this->getContainer()->get('orob2b_pricing.resolver.combined_product_price_resolver');
        foreach ($cplIterator as $cpl) {
            $priceResolver->combinePrices($cpl);
        }
    }

    /**
     * @param PriceList $priceList
     */
    protected function updateCPLByPriceList(PriceList $priceList)
    {

    }

    /**
     * @param InputInterface $input
     * @return array|Website[]
     */
    protected function getWebsites(InputInterface $input)
    {
        $websiteIds = $input->getOption(self::WEBSITE);
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
