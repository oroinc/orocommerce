<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListCollectionProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /** @var  ConfigManager */
    protected $configManager;

    /** @var  PriceListConfigConverter */
    protected $configConverter;

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     * @param PriceListConfigConverter $configConverter
     */
    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        PriceListConfigConverter $configConverter
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->configConverter = $configConverter;
    }

    /**
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByConfig()
    {
        /** @var PriceListConfig[] $priceListsConfig */
        $priceListsConfig = $this->configConverter->convertFromSaved(
            $this->configManager->get('oro_b2b_pricing.default_price_lists')
        );
        $priceListCollection = [];
        foreach ($priceListsConfig as $priceListConfig) {
            $priceListCollection[] = new PriceListSequenceMember(
                $priceListConfig->getPriceList(),
                $priceListConfig->isMergeAllowed()
            );
        }

        return $priceListCollection;
    }

    /**
     * @param Website $website
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByWebsite(Website $website)
    {
        /** @var PriceListToWebsiteRepository $repo */
        $repo = $this->getRepository('OroB2BPricingBundle:PriceListToWebsite');
        $priceListsToWebsite = $repo->getPriceLists($website);
        $priceListCollection = [];
        foreach ($priceListsToWebsite as $priceListToWebsite) {
            $priceListCollection[] = new PriceListSequenceMember(
                $priceListToWebsite->getPriceList(),
                $priceListToWebsite->isMergeAllowed()
            );
        }
        $fallbackEntity = $this->registry
            ->getRepository('OroB2BPricingBundle:PriceListWebsiteFallback')
            ->findOneBy(['website' => $website]);
        if ($fallbackEntity->getFallback()) {
            return array_merge($priceListCollection, $this->getPriceListsByConfig());
        }

        return $priceListCollection;
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByAccountGroup(AccountGroup $accountGroup, Website $website)
    {
        /** @var PriceListToAccountGroupRepository $repo */
        $repo = $this->getRepository('OroB2BPricingBundle:PriceListToAccountGroup');
        $priceListsToAccountGroup = $repo->getPriceLists($accountGroup, $website);
        $priceListCollection = [];
        foreach ($priceListsToAccountGroup as $priceListToAccountGroup) {
            $priceListCollection[] = new PriceListSequenceMember(
                $priceListToAccountGroup->getPriceList(),
                $priceListToAccountGroup->isMergeAllowed()
            );
        }
        $fallbackEntity = $this->registry
            ->getRepository('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->findOneBy(['accountGroup' => $accountGroup]);
        if ($fallbackEntity->getFallback()) {
            return array_merge($priceListCollection, $this->getPriceListsByWebsite($website));
        }

        return $priceListCollection;
    }


    /**
     * @param Account $account
     * @param Website $website
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByAccount(Account $account, Website $website)
    {
        /** @var PriceListToAccountRepository $repo */
        $repo = $this->getRepository('OroB2BPricingBundle:PriceListToAccount');
        $priceListsToAccount = $repo->getPriceLists($account, $website);
        $priceListCollection = [];
        foreach ($priceListsToAccount as $priceListToAccount) {
            $priceListCollection[] = new PriceListSequenceMember(
                $priceListToAccount->getPriceList(),
                $priceListToAccount->isMergeAllowed()
            );
        }
        $fallbackEntity = $this->registry
            ->getRepository('OroB2BPricingBundle:PriceListAccountFallback')
            ->findOneBy(['account' => $account]);
        if ($fallbackEntity->getFallback()) {
            return array_merge(
                $priceListCollection,
                $this->getPriceListsByAccountGroup($account->getGroup(), $website)
            );
        }

        return $priceListCollection;
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    public function getRepository($className)
    {
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className);
    }
}
