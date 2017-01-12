<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;

class PriceListTreeHandler
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
     * @var PriceListRepository
     */
    protected $priceListRepository;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var BasePriceList[]
     */
    protected $priceLists = [];

    /**
     * @param ManagerRegistry $registry
     * @param WebsiteManager $websiteManager
     * @param ConfigManager $configManager
     */
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
        if ($customer) {
            $priceList = $this->getPriceListByCustomer($customer, $website);
        }
        if (!$priceList) {
            $priceList = $this->getPriceListRepository()->getPriceListByWebsite($website);
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
     * @return null|CombinedPriceList
     */
    protected function getPriceListByCustomer(Customer $customer, Website $website)
    {
        if ($customer->getId()) {
            $priceList = $this->getPriceListRepository()->getPriceListByCustomer($customer, $website);
            if ($priceList) {
                return $priceList;
            }
        }

        return $this->getPriceListByCustomerGroup($customer, $website);
    }

    /**
     * @param Customer|null $customer
     * @param Website|null $website
     * @return null|CombinedPriceList
     */
    protected function getPriceListByCustomerGroup(Customer $customer, Website $website)
    {
        $priceList = null;
        $customerGroup = $customer->getGroup();
        if ($customerGroup && $customerGroup->getId()) {
            $priceList = $this->getPriceListRepository()->getPriceListByCustomerGroup($customerGroup, $website);
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

    /**
     * @return null|BasePriceList
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
    protected function getPriceListRepository()
    {
        if (!$this->priceListRepository) {
            $this->priceListRepository = $this->registry
                ->getManagerForClass(CombinedPriceList::class)
                ->getRepository(CombinedPriceList::class);
        }

        return $this->priceListRepository;
    }
}
