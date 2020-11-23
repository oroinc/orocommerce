<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides actual Combined Price List for given Customer and Website. This parameters are not mandatory and in case
 * when they not passed will try to get Price List from runtime (if logged in as Anonymous user)
 * or from System Configuration.
 */
class CombinedPriceListTreeHandler extends AbstractPriceListTreeHandler
{
    /**
     * @var CombinedPriceListRepository
     */
    private $priceListRepository;

    /**
     * {@inheritDoc}
     */
    protected function loadPriceListByCustomer(Customer $customer, Website $website)
    {
        return $this->getPriceListRepository()->getPriceListByCustomer($customer, $website);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadPriceListByCustomerGroup(CustomerGroup $customerGroup, Website $website)
    {
        return $this->getPriceListRepository()->getPriceListByCustomerGroup($customerGroup, $website);
    }

    /**
     * @param Website $website
     * @return CombinedPriceList|null
     */
    protected function getPriceListByWebsite(Website $website)
    {
        return $this->getPriceListRepository()->getPriceListByWebsite($website);
    }

    /**
     * @return null|CombinedPriceList
     */
    protected function getPriceListFromConfig()
    {
        $key = Configuration::getConfigKeyToPriceList();
        $priceListId = $this->configManager->get($key);

        if (!$priceListId) {
            return null;
        }

        return $this->getPriceListRepository()->find($priceListId);
    }

    /**
     * @return CombinedPriceListRepository
     */
    private function getPriceListRepository()
    {
        if (!$this->priceListRepository) {
            $this->priceListRepository = $this->registry
                ->getManagerForClass(CombinedPriceList::class)
                ->getRepository(CombinedPriceList::class);
        }

        return $this->priceListRepository;
    }
}
