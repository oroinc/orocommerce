<?php

namespace OroB2B\Bundle\PricingBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;

use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
        $force = false;
        if ($input->getOption(self::FORCE)) {
            $force = true;
        }

        $websites = $this->getRecalculatedWebsites($input->getOption(self::WEBSITE));
        $accountGroups = $this->getRecalculatedAccountGroups($input->getOption(self::ACCOUNT_GROUP));
        $accounts = $this->getRecalculatedAccounts($input->getOption(self::ACCOUNT));

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceListChangeTrigger');

        if (empty($websites) && empty($accountGroups) && empty($accounts) && $force) {
            $priceListChangeTrigger = new PriceListChangeTrigger();

            $em->persist($priceListChangeTrigger);
        } else {
            $this->preparePriceListChangeTriggersForScopes($em, $websites, $accountGroups, $accounts);
        }

        $em->flush();

        $container = $this->getContainer();
        /** @var CombinedPriceListQueueConsumer $consumer */
        $priceListCollectionConsumer = $container->get('orob2b_pricing.builder.queue_consumer');
        $productPriceConsumer = $container->get('orob2b_pricing.builder.combined_product_price_queue_consumer');
        $key = Configuration::getConfigKeyByName(Configuration::PRICE_LISTS_UPDATE_MODE);
        $mode = $container->get('oro_config.manager')->get($key);

        if ($force || $mode === CombinedPriceListQueueConsumer::MODE_SCHEDULED) {
            $output->writeln('<info>Start the process recalculation</info>');
            $behavior = $force ? CombinedPriceListProvider::BEHAVIOR_FORCE : null;
            $priceListCollectionConsumer->process($behavior, $force);
            $productPriceConsumer->process();
            $output->writeln('<info>The cache is updated successfully</info>');
        } else {
            $output->writeln('<info>Recalculation is not required, another mode is active</info>');
        }

        $this->calculatePricesForScheduleCPLs();
    }

    /**
     * @param EntityManager $em
     * @param $websites
     * @param $accountGroups
     * @param $accounts
     */
    protected function preparePriceListChangeTriggersForScopes(EntityManager $em, $websites, $accountGroups, $accounts)
    {
        if (empty($websites) && (!empty($accountGroups) || !empty($accounts))) {
            $websites = $this->getContainer()->get('doctrine')
                ->getRepository('OroB2BWebsiteBundle:Website')->findAll();
        }

        foreach ($websites as $website) {
            if (empty($accountGroups) && empty($accounts)) {
                $this->persistWebsiteScopeTrigger($em, $website);
            }

            if ($accountGroups) {
                $this->persistAccountGroupScopeTriggers($em, $website, $accountGroups);
            }

            if ($accounts) {
                $this->persistAccountScopeTriggers($em, $website, $accounts);
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param Website $website
     */
    protected function persistWebsiteScopeTrigger(EntityManager $em, Website $website)
    {
        $priceListChangeTriggerForWebsite = new PriceListChangeTrigger();
        $priceListChangeTriggerForWebsite->setWebsite($website);

        $em->persist($priceListChangeTriggerForWebsite);
    }

    /**
     * @param EntityManager $em
     * @param Website $website
     * @param AccountGroup[] $accountGroups
     */
    protected function persistAccountGroupScopeTriggers(EntityManager $em, Website $website, array $accountGroups)
    {
        foreach ($accountGroups as $accountGroup) {
            $priceListChangeTriggerForAccountGroup = new PriceListChangeTrigger();
            $priceListChangeTriggerForAccountGroup->setWebsite($website);
            $priceListChangeTriggerForAccountGroup->setAccountGroup($accountGroup);

            $em->persist($priceListChangeTriggerForAccountGroup);
        }
    }

    /**
     * @param EntityManager $em
     * @param Website $website
     * @param Account[] $accounts
     */
    protected function persistAccountScopeTriggers(EntityManager $em, Website $website, array $accounts)
    {
        foreach ($accounts as $account) {
            $priceListChangeTriggerForAccount = new PriceListChangeTrigger();
            $priceListChangeTriggerForAccount->setWebsite($website);
            $priceListChangeTriggerForAccount->setAccount($account);

            $em->persist($priceListChangeTriggerForAccount);
        }
    }

    /**
     * @param array $websiteIds
     * @return array|Website[]
     */
    protected function getRecalculatedWebsites(array $websiteIds)
    {
        $websites = [];

        if (!empty($websiteIds)) {
            $websites = $this->getContainer()->get('doctrine')
                ->getRepository('OroB2BWebsiteBundle:Website')
                ->findBy(['id' => $websiteIds]);
        }

        return $websites;
    }

    /**
     * @param array $accountGroupIds
     * @return array|AccountGroup[]
     */
    protected function getRecalculatedAccountGroups(array $accountGroupIds)
    {
        $accountGroups = [];

        if (!empty($accountGroupIds)) {
            $accountGroups = $this->getContainer()->get('doctrine')
                ->getRepository('OroB2BAccountBundle:AccountGroup')
                ->findBy(['id' => $accountGroupIds]);
        }

        return $accountGroups;
    }

    /**
     * @param array $accountIds
     * @return array|Account[]
     */
    protected function getRecalculatedAccounts(array $accountIds)
    {
        $accounts = [];

        if (!empty($accountIds)) {
            $accounts = $this->getContainer()->get('doctrine')
                ->getRepository('OroB2BAccountBundle:Account')
                ->findBy(['id' => $accountIds]);
        }

        return $accounts;
    }

    protected function calculatePricesForScheduleCPLs()
    {
        $offsetHours = $this->getCPLProcessingOffsetsByConfig();

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
     * @return mixed
     */
    protected function getCPLProcessingOffsetsByConfig()
    {
        return $this->getContainer()->get('oro_config.manager')->get('oro_b2b_pricing.offset_of_processing_cpl_prices');
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }
}
