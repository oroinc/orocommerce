<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerGroupRepository;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Provides actual Price List for given Customer and Website. This parameters are not mandatory and in case
 * when they not passed will try to get Price List from runtime (if logged in as Anonymous user)
 * or from System Configuration.
 */
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
     * @var TokenAccessorInterface
     */
    protected $tokenAccessor;

    /**
     * @var CombinedPriceList[]
     */
    protected $priceLists = [];

    /**
     * @param ManagerRegistry $registry
     * @param WebsiteManager $websiteManager
     * @param ConfigManager $configManager
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        ManagerRegistry $registry,
        WebsiteManager $websiteManager,
        ConfigManager $configManager,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->registry = $registry;
        $this->websiteManager = $websiteManager;
        $this->configManager = $configManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param Customer|null $customer
     * @param Website|null $website
     * @return CombinedPriceList|null
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
            $priceList = $this->getPriceListByAnonymousCustomerGroup($website);
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
     * @param Website|null $website
     * @return null|CombinedPriceList
     */
    protected function getPriceListByAnonymousCustomerGroup(Website $website)
    {
        $priceList = null;
        $customerGroup = $this->getAnonymousCustomerGroup();
        if ($customerGroup) {
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
    protected function getPriceListRepository()
    {
        if (!$this->priceListRepository) {
            $this->priceListRepository = $this->registry
                ->getManagerForClass(CombinedPriceList::class)
                ->getRepository(CombinedPriceList::class);
        }

        return $this->priceListRepository;
    }

    /**
     * @return CustomerGroup|null
     */
    private function getAnonymousCustomerGroup()
    {
        if (!$this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken) {
            return null;
        }

        $id = (int)$this->configManager->get('oro_customer.anonymous_customer_group');

        return $id ? $this->getCustomerGroupRepository()->find($id) : null;
    }

    /**
     * @return CustomerGroupRepository
     */
    private function getCustomerGroupRepository()
    {
        return $this->registry
            ->getManagerForClass(CustomerGroup::class)
            ->getRepository(CustomerGroup::class);
    }
}
