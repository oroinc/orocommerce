<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Provides actual Base Price List for given Customer and Website. This parameters are not mandatory and in case
 * when they not passed will try to get Price List from runtime (if logged in as Anonymous user)
 * or from System Configuration.
 */
abstract class AbstractPriceListTreeHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var BasePriceList[]
     */
    protected $priceLists = [];

    public function __construct(
        ManagerRegistry $registry,
        WebsiteManager $websiteManager,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->websiteManager = $websiteManager;
        $this->configManager = $configManager;
    }

    /**
     * @param Website $website
     * @return BasePriceList|null
     */
    abstract protected function getPriceListByWebsite(Website $website);

    /**
     * @return null|BasePriceList
     */
    abstract protected function getPriceListFromConfig();

    /**
     * @param Customer $customer
     * @param Website $website
     * @return BasePriceList
     */
    abstract protected function loadPriceListByCustomer(Customer $customer, Website $website);

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @return BasePriceList
     */
    abstract protected function loadPriceListByCustomerGroup(CustomerGroup $customerGroup, Website $website);

    /**
     * @param Customer|null $customer
     * @param Website|null $website
     * @return BasePriceList|null
     */
    public function getPriceList(Customer $customer = null, Website $website = null)
    {
        if (!$website) {
            $website = $this->websiteManager->getCurrentWebsite();
        }

        $key = $this->getUniqueKey($customer, $website);
        if (array_key_exists($key, $this->priceLists)) {
            return $this->priceLists[$key];
        }

        $priceList = null;
        if ($website) {
            if ($customer) {
                $priceList = $this->getPriceListByCustomer($customer, $website);
            }
            if (!$priceList) {
                $priceList = $this->getPriceListByWebsite($website);
            }
        }

        if (!$priceList) {
            $priceList = $this->getPriceListFromConfig();
        }
        $this->priceLists[$key] = $priceList;

        return $priceList;
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @return null|BasePriceList
     */
    protected function getPriceListByCustomer(Customer $customer, Website $website)
    {
        if ($customer->getId()) {
            $priceList = $this->loadPriceListByCustomer($customer, $website);
            if ($priceList) {
                return $priceList;
            }
        }

        return $this->getPriceListByCustomerGroup($customer, $website);
    }

    /**
     * @param Customer|null $customer
     * @param Website|null $website
     * @return null|BasePriceList
     */
    protected function getPriceListByCustomerGroup(Customer $customer, Website $website)
    {
        $priceList = null;
        $customerGroup = $customer->getGroup();
        if ($customerGroup && $customerGroup->getId()) {
            $priceList = $this->loadPriceListByCustomerGroup($customerGroup, $website);
        }

        return $priceList;
    }

    /**
     * @param Customer|null $customer
     * @param Website|null $website
     * @return string
     */
    protected function getUniqueKey(Customer $customer = null, Website $website = null)
    {
        $key = '';
        if ($customer) {
            $key .= spl_object_hash($customer);
        }
        if ($website) {
            $key .= spl_object_hash($website);
        }
        return $key;
    }
}
