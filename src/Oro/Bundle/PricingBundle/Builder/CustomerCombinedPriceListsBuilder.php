<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Updates or creates combined price lists for customer scope
 *
 * @method PriceListToCustomerRepository getPriceListToEntityRepository()
 */
class CustomerCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @param Website $website
     * @param Customer $customer
     * @param int|null $forceTimestamp
     */
    public function build(Website $website, Customer $customer, $forceTimestamp = null)
    {
        if (!$this->isBuiltForCustomer($website, $customer)) {
            $this->wrapInTransaction(function () use ($website, $customer, $forceTimestamp) {
                $this->updatePriceListsOnCurrentLevel($website, $customer, $forceTimestamp);
            });
            $this->garbageCollector->cleanCombinedPriceLists();
            $this->setBuiltForCustomer($website, $customer);
        }
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     * @param int|null $forceTimestamp
     */
    public function buildByCustomerGroup(Website $website, CustomerGroup $customerGroup, $forceTimestamp = null)
    {
        if (!$this->isBuiltForCustomerGroup($website, $customerGroup)) {
            $fallback = $forceTimestamp ? null : PriceListCustomerFallback::ACCOUNT_GROUP;
            $customers = $this->getPriceListToEntityRepository()
                ->getCustomerIteratorByDefaultFallback($customerGroup, $website, $fallback);

            foreach ($customers as $customer) {
                $this->wrapInTransaction(function () use ($website, $customer, $forceTimestamp) {
                    $this->updatePriceListsOnCurrentLevel($website, $customer, $forceTimestamp);
                });
            }
            $this->setBuiltForCustomerGroup($website, $customerGroup);
        }
    }

    /**
     * @param Website $website
     * @param Customer $customer
     * @param int|null $forceTimestamp
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, Customer $customer, $forceTimestamp = null)
    {
        $priceListsToCustomer = $this->getPriceListToEntityRepository()
            ->findOneBy(['website' => $website, 'customer' => $customer]);
        $hasFallbackOnNextLevel = $this->hasFallbackOnNextLevel($website, $customer);

        if (!$priceListsToCustomer) {
            /** @var PriceListToCustomerRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($customer, $website);

            if ($hasFallbackOnNextLevel) {
                //is this case price list would be fetched from next level, and there is no need to store the own
                return;
            }
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByCustomer($customer, $website);
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);

        if ($hasFallbackOnNextLevel
            && ($fallbackPriceLists = $this->getFallbackPriceLists($website, $customer))
            && !$this->priceListCollectionProvider->containMergeDisallowed($collection)
            && !$this->priceListCollectionProvider->containScheduled($collection)
        ) {
            $currentLevelPriceLists = array_splice($collection, 0, -\count($fallbackPriceLists));

            $this->updateRelationsAndPricesUsingFallback(
                $combinedPriceList,
                $website,
                $currentLevelPriceLists,
                $fallbackPriceLists,
                $customer,
                $forceTimestamp
            );
        } else {
            $this->updateRelationsAndPrices($combinedPriceList, $website, $customer, $forceTimestamp);
        }
    }

    /**
     * @param Website $website
     * @param Customer $customer
     * @return array|PriceListSequenceMember[]
     */
    protected function getFallbackPriceLists(Website $website, Customer $customer)
    {
        if ($customer->getGroup()) {
            return $this->priceListCollectionProvider
                ->getPriceListsByCustomerGroup($customer->getGroup(), $website);
        }

        return null;
    }

    /**
     * @param Website $website
     * @param Customer $customer
     * @return bool
     */
    protected function isBuiltForCustomer(Website $website, Customer $customer)
    {
        return !empty($this->builtList['customer'][$website->getId()][$customer->getId()]);
    }

    /**
     * @param Website $website
     * @param Customer $customer
     */
    protected function setBuiltForCustomer(Website $website, Customer $customer)
    {
        $this->builtList['customer'][$website->getId()][$customer->getId()] = true;
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     * @return bool
     */
    protected function isBuiltForCustomerGroup(Website $website, CustomerGroup $customerGroup)
    {
        return !empty($this->builtList['group'][$website->getId()][$customerGroup->getId()]);
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     */
    protected function setBuiltForCustomerGroup(Website $website, CustomerGroup $customerGroup)
    {
        $this->builtList['group'][$website->getId()][$customerGroup->getId()] = true;
    }

    /**
     * @param Website $website
     * @param Customer $customer
     * @return bool
     */
    public function hasFallbackOnNextLevel(Website $website, Customer $customer)
    {
        $fallback = $this->getFallbackRepository()->findOneBy(
            [
                'website' => $website,
                'customer' => $customer,
                'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY
            ]
        );

        return $fallback === null;
    }
}
