<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class PriceListProvider
{
    /**
     * @var string
     */
    protected $priceListClass;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PriceListRepository
     */
    protected $repository;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param ManagerRegistry $registry
     * @param WebsiteManager $websiteManager
     */
    public function __construct(ManagerRegistry $registry, WebsiteManager $websiteManager)
    {
        $this->registry = $registry;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @return PriceList
     */
    public function getDefaultPriceList()
    {
        return $this->getRepository()->getDefault();
    }

    /**
     * @param Website|null $website
     * @return PriceList
     */
    public function getPriceListByWebsite(Website $website = null)
    {
        if (!$website) {
            $website = $this->websiteManager->getCurrentWebsite();
        }

        /** @var PriceList $priceList */
        $priceList = $this->getRepository()->getPriceListByWebsite($website);

        if (!$priceList) {
            $priceList = $this->getDefaultPriceList();
        }

        return $priceList;
    }

    /**
     * @param AccountGroup $accountGroup
     * @return PriceList
     */
    public function getPriceListByAccountGroup(AccountGroup $accountGroup)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getRepository()->getPriceListByAccountGroup($accountGroup);

        if (!$priceList) {
            $priceList = $this->getPriceListByWebsite();
        }

        return $priceList;
    }

    /**
     * @param Account $account
     * @return PriceList
     */
    public function getPriceListByAccount(Account $account)
    {
        $priceList = $this->getRepository()->getPriceListByAccount($account);

        if (!$priceList) {
            $priceList = $this->getPriceListByAccountGroup($account->getGroup());
        }

        return $priceList;
    }

    /**
     * @param string $priceListClass
     * @return PriceListProvider
     */
    public function setPriceListClass($priceListClass)
    {
        $this->priceListClass = $priceListClass;

        return $this;
    }

    /**
     * @return PriceListRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->registry->getManagerForClass($this->priceListClass)
                ->getRepository($this->priceListClass);
        }

        return $this->repository;
    }
}
