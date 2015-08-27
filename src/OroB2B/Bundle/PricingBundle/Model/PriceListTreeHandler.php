<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class PriceListTreeHandler
{
    /** @var ManagerRegistry */
    protected $registry;

    /**  @var WebsiteManager */
    protected $websiteManager;

    /**  @var string */
    protected $priceListClass;

    /**  @var PriceListRepository */
    protected $priceListRepository;

    /**
     * @param ManagerRegistry $registry
     * @param WebsiteManager $websiteManager
     * @param string $priceListClass
     */
    public function __construct(ManagerRegistry $registry, WebsiteManager $websiteManager, $priceListClass)
    {
        $this->registry = $registry;
        $this->websiteManager = $websiteManager;
        $this->priceListClass = $priceListClass;
    }

    /**
     * @param AccountUser|null $accountUser
     * @return PriceList
     */
    public function getPriceList(AccountUser $accountUser = null)
    {
        if ($accountUser) {
            $account = $accountUser->getAccount();

            if ($account) {
                $priceList = $this->getPriceListRepository()->getPriceListByAccount($account);
                if ($priceList) {
                    return $priceList;
                }

                $priceList = $this->getPriceListFromAccountTree($account);
                if ($priceList) {
                    return $priceList;
                }

                $priceList = $this->getPriceListFromAccountGroup($account);
                if ($priceList) {
                    return $priceList;
                }

                $priceList = $this->getPriceListFromAccountGroupTree($account);
                if ($priceList) {
                    return $priceList;
                }
            }
        }

        $priceList = $this->getPriceListFromWebsite();
        if ($priceList) {
            return $priceList;
        }

        return $this->getPriceListRepository()->getDefault();
    }

    /**
     * @param Account $account
     * @return null|PriceList
     */
    protected function getPriceListFromAccountTree(Account $account)
    {
        $parentAccount = $account->getParent();
        if (!$parentAccount) {
            return null;
        }

        while ($parentAccount) {
            $priceList = $this->getPriceListRepository()->getPriceListByAccount($parentAccount);
            if ($priceList) {
                return $priceList;
            }

            $parentAccount = $parentAccount->getParent();
        }

        return null;
    }

    /**
     * @param Account $account
     * @return null|PriceList
     */
    protected function getPriceListFromAccountGroupTree(Account $account)
    {
        $parentAccount = $account->getParent();
        if (!$parentAccount) {
            return null;
        }

        while ($parentAccount) {
            $parentGroup = $parentAccount->getGroup();
            if ($parentGroup) {
                $priceList = $this->getPriceListRepository()->getPriceListByAccountGroup($parentGroup);
                if ($priceList) {
                    return $priceList;
                }
            }

            $parentAccount = $parentAccount->getParent();
        }

        return null;
    }

    /**
     * @return null|PriceList
     */
    protected function getPriceListFromWebsite()
    {
        $website = $this->websiteManager->getCurrentWebsite();
        if (!$website) {
            return null;
        }

        $priceList = $this->getPriceListRepository()->getPriceListByWebsite($website);
        if ($priceList) {
            return $priceList;
        }

        return null;
    }

    /**
     * @param Account $account
     * @return null|PriceList
     */
    protected function getPriceListFromAccountGroup(Account $account)
    {
        $accountGroup = $account->getGroup();
        if (!$accountGroup) {
            return null;
        }

        $priceList = $this->getPriceListRepository()->getPriceListByAccountGroup($accountGroup);
        if ($priceList) {
            return $priceList;
        }

        return null;
    }

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository()
    {
        if (!$this->priceListRepository) {
            $this->priceListRepository = $this->registry->getManagerForClass($this->priceListClass)
                ->getRepository($this->priceListClass);
        }

        return $this->priceListRepository;
    }
}
