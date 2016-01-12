<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountCombinedPriceListsBuilder
{
    /**
     * @var PriceListCollectionProvider
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider
     */
    protected $combinedPriceListProvider;

    /**
     * @var string
     */
    protected $priceListToAccountClassName;

    /**
     * @var string
     */
    protected $combinedPriceListToAccountClassName;


    /**
     * @var PriceListToAccountRepository
     */
    protected $combinedPriceListToAccountRepository;

    /**
     * @var PriceListToAccountRepository
     */
    protected $priceListToAccountRepository;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    protected $combinedPriceListGarbageCollector;

    /**
     * @param $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Website $website
     * @param Account $account
     */
    public function build(Account $account, Website $website)
    {
        $this->updatePriceListsOnCurrentLevel($account, $website);
        $this->combinedPriceListGarbageCollector->cleanCombinedPriceLists();
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @param bool $needToCleanGarbage
     */
    public function buildByAccountGroup(AccountGroup $accountGroup, Website $website, $needToCleanGarbage = false)
    {
        $groupsIterator = $this->getPriceListToAccountRepository()
            ->getPriceListToAccountByWebsiteIterator($accountGroup, $website);
        /**
         * @var $accountToPriceList PriceListToAccount
         */
        foreach ($groupsIterator as $accountToPriceList) {
            $this->updatePriceListsOnCurrentLevel($accountToPriceList->getAccount(), $website);
        }
        if ($needToCleanGarbage) {
            $this->combinedPriceListGarbageCollector->cleanCombinedPriceLists();
        }
    }

    /**
     * @param CombinedPriceListGarbageCollector $CPLGarbageCollector
     */
    public function setCombinedPriceListGarbageCollector(CombinedPriceListGarbageCollector $CPLGarbageCollector)
    {
        $this->combinedPriceListGarbageCollector = $CPLGarbageCollector;
    }

    /**
     * @return PriceListToAccountRepository
     */
    public function getPriceListToAccountRepository()
    {
        if (!$this->priceListToAccountRepository) {
            $class = $this->priceListToAccountClassName;
            $this->priceListToAccountRepository = $this->registry->getManagerForClass($class)
                ->getRepository($class);
        }

        return $this->priceListToAccountRepository;
    }

    /**
     * @param CombinedPriceListProvider $combinedPriceListProvider
     */
    public function setCombinedPriceListProvider($combinedPriceListProvider)
    {
        $this->combinedPriceListProvider = $combinedPriceListProvider;
    }

    /**
     * @param PriceListCollectionProvider $priceListCollectionProvider
     */
    public function setPriceListCollectionProvider($priceListCollectionProvider)
    {
        $this->priceListCollectionProvider = $priceListCollectionProvider;
    }

    /**
     * @param mixed $priceListToAccountClassName
     */
    public function setCombinedPriceListToAccountClassName($priceListToAccountClassName)
    {
        $this->combinedPriceListToAccountClassName = $priceListToAccountClassName;
        $this->combinedPriceListToAccountRepository = null;
    }

    /**
     * @param mixed $priceListToAccountClassName
     */
    public function setPriceListToAccountClassName($priceListToAccountClassName)
    {
        $this->priceListToAccountClassName = $priceListToAccountClassName;
        $this->priceListToAccountRepository = null;
    }

    /**
     * @param Website $website
     * @param Account $account
     */
    protected function updatePriceListsOnCurrentLevel(Account $account, Website $website)
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByAccount($account, $website);
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);

        $relation = $this->getCombinedPriceListToAccountRepository()
            ->findByPrimaryKey($actualCombinedPriceList, $account, $website);

        if (!$relation) {
            $this->connectNewPriceList($account, $actualCombinedPriceList);
        }
    }

    /**
     * @param Account $account
     * @param CombinedPriceList $combinedPriceList
     */
    protected function connectNewPriceList(Account $account, CombinedPriceList $combinedPriceList)
    {
        $relation = $this->getCombinedPriceListToAccountRepository()->findOneBy(['account' => $account]);
        $manager = $this->registry->getManagerForClass($this->combinedPriceListToAccountClassName);
        if (!$relation) {
            $relation = new CombinedPriceListToAccount();
            $relation->setPriceList($combinedPriceList);
            $relation->setAccount($account);
            $manager->persist($relation);
        }
        $relation->setPriceList($combinedPriceList);
        $manager->flush();
    }

    /**
     * @return PriceListToAccountRepository
     */
    protected function getCombinedPriceListToAccountRepository()
    {
        if (!$this->combinedPriceListToAccountRepository) {
            $class = $this->combinedPriceListToAccountClassName;
            $this->combinedPriceListToAccountRepository = $this->registry->getManagerForClass($class)
                ->getRepository($class);
        }

        return $this->combinedPriceListToAccountRepository;
    }
}
