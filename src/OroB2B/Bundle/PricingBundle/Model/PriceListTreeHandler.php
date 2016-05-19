<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

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
     * @var string
     */
    protected $priceListClass;

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
     * @param Account|null $account
     * @param Website|null $website
     * @return BasePriceList|null
     */
    public function getPriceList(Account $account = null, Website $website = null)
    {
        if (!$website) {
            $website = $this->websiteManager->getCurrentWebsite();
        }

        $key = $this->getUniqueKey($account, $website);
        if (array_key_exists($key, $this->priceLists)) {
            return $this->priceLists[$key];
        }

        $priceList = null;
        if ($account) {
            $priceList = $this->getPriceListByAccount($account, $website);
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
     * @param Account|null $account
     * @param Website|null $website
     * @return null|CombinedPriceList
     */
    protected function getPriceListByAccount(Account $account = null, Website $website = null)
    {
        if ($account->getId()) {
            $priceList = $this->getPriceListRepository()->getPriceListByAccount($account, $website);
            if ($priceList) {
                return $priceList;
            }
        }

        return $this->getPriceListByAccountGroup($account, $website);
    }

    /**
     * @param Account|null $account
     * @param Website|null $website
     * @return null|CombinedPriceList
     */
    protected function getPriceListByAccountGroup(Account $account = null, Website $website = null)
    {
        $priceList = null;
        $accountGroup = $account->getGroup();
        if ($accountGroup && $accountGroup->getId()) {
            $priceList = $this->getPriceListRepository()->getPriceListByAccountGroup($accountGroup, $website);
        }

        return $priceList;
    }

    /**
     * @param Account|null $account
     * @param Website|null $website
     * @return string
     */
    protected function getUniqueKey(Account $account = null, Website $website = null)
    {
        $key = '';
        if ($account) {
            $key .= spl_object_hash($account);
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
            $this->priceListRepository = $this->registry->getManagerForClass($this->priceListClass)
                ->getRepository($this->priceListClass);
        }

        return $this->priceListRepository;
    }

    /**
     * @param string $priceListClass
     */
    public function setPriceListClass($priceListClass)
    {
        $this->priceListClass = $priceListClass;
        $this->priceListRepository = null;
    }
}
