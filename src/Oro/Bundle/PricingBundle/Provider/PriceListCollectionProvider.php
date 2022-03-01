<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides prices lists assigned to one of defined levels: config, website, customer group, customer
 */
class PriceListCollectionProvider
{
    protected ManagerRegistry $registry;
    protected ConfigManager $configManager;
    protected PriceListConfigConverter $configConverter;

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
            $this->configManager->get('oro_pricing.default_price_lists')
        );
        $activeRelations = [];
        foreach ($priceListsConfig as $priceList) {
            if ($priceList->getPriceList()->isActive()) {
                $activeRelations[] = $priceList;
            }
        }

        return $this->getPriceListSequenceMembers($activeRelations);
    }

    /**
     * @param Website $website
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByWebsite(Website $website)
    {
        /** @var PriceListToWebsiteRepository $repo */
        $repo = $this->registry->getRepository(PriceListToWebsite::class);
        $priceListCollection = $this->getPriceListSequenceMembers(
            $repo->getPriceLists($website)
        );
        $fallbackEntity = $this->registry
            ->getRepository(PriceListWebsiteFallback::class)
            ->findOneBy(['website' => $website]);
        if (!$fallbackEntity || $fallbackEntity->getFallback() === PriceListWebsiteFallback::CONFIG) {
            return array_merge($priceListCollection, $this->getPriceListsByConfig());
        }

        return $priceListCollection;
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByCustomerGroup(CustomerGroup $customerGroup, Website $website)
    {
        /** @var PriceListToCustomerGroupRepository $repo */
        $repo = $this->registry->getRepository(PriceListToCustomerGroup::class);
        $priceListCollection = $this->getPriceListSequenceMembers(
            $repo->getPriceLists($customerGroup, $website)
        );
        $fallbackEntity = $this->registry
            ->getRepository(PriceListCustomerGroupFallback::class)
            ->findOneBy(['customerGroup' => $customerGroup, 'website' => $website]);
        if (!$fallbackEntity || $fallbackEntity->getFallback() === PriceListCustomerGroupFallback::WEBSITE) {
            return array_merge($priceListCollection, $this->getPriceListsByWebsite($website));
        }

        return $priceListCollection;
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @return PriceListSequenceMember[]
     */
    public function getPriceListsByCustomer(Customer $customer, Website $website)
    {
        /** @var PriceListToCustomerRepository $repo */
        $repo = $this->registry->getRepository(PriceListToCustomer::class);
        $priceListCollection = $this->getPriceListSequenceMembers(
            $repo->getPriceLists($customer, $website)
        );

        $fallbackEntity = $this->registry
            ->getRepository(PriceListCustomerFallback::class)
            ->findOneBy(['customer' => $customer, 'website' => $website]);

        if ($this->isFallbackToCurrentCustomerOnly($fallbackEntity)) {
            $priceLists = $priceListCollection;
        } elseif ($customer->getGroup() && $this->isFallbackToCustomerGroup($fallbackEntity)) {
            $priceLists = array_merge(
                $priceListCollection,
                $this->getPriceListsByCustomerGroup($customer->getGroup(), $website)
            );
        } else {
            $priceLists = array_merge($priceListCollection, $this->getPriceListsByWebsite($website));
        }

        return $priceLists;
    }

    /**
     * @param BasePriceListRelation[]|PriceListConfig[] $priceListsRelations
     * @return PriceListSequenceMember[]
     */
    protected function getPriceListSequenceMembers($priceListsRelations)
    {
        $priceListCollection = [];
        foreach ($priceListsRelations as $priceListsRelation) {
            $priceListCollection[] = new PriceListSequenceMember(
                $priceListsRelation->getPriceList(),
                $priceListsRelation->isMergeAllowed()
            );
        }

        return $priceListCollection;
    }

    /**
     * @param PriceListCustomerFallback|null $fallbackEntity
     * @return bool
     */
    protected function isFallbackToCurrentCustomerOnly(PriceListCustomerFallback $fallbackEntity = null): bool
    {
        return $fallbackEntity && $fallbackEntity->getFallback() === PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY;
    }

    /**
     * @param PriceListCustomerFallback|null $fallbackEntity
     * @return bool
     */
    protected function isFallbackToCustomerGroup(PriceListCustomerFallback $fallbackEntity = null): bool
    {
        return !$fallbackEntity || $fallbackEntity->getFallback() === PriceListCustomerFallback::ACCOUNT_GROUP;
    }
}
