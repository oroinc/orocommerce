<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteCombinedPriceListsBuilder
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
    protected $priceListToWebsiteClassName;

    /**
     * @var string
     */
    protected $combinedPriceListToWebsiteClassName;


    /**
     * @var PriceListToWebsiteRepository
     */
    protected $combinedPriceListToWebsiteRepository;

    /**
     * @var PriceListToWebsiteRepository
     */
    protected $priceListToWebsiteRepository;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AccountGroupCombinedPriceListsBuilder
     */
    protected $accountGroupCombinedPriceListsBuilder;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    protected $combinedPriceListGarbageCollector;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Website|null $currentWebsite
     * @param boolean|false $force
     */
    public function build(Website $currentWebsite = null, $force = false)
    {
        $websites = [$currentWebsite];
        if (!$currentWebsite) {
            $websites = $this->getPriceListToWebsiteRepository()
                ->getWebsiteIteratorByFallback(PriceListWebsiteFallback::CONFIG);
        }

        foreach ($websites as $website) {
            $this->updatePriceListsOnCurrentLevel($website, $force);
            $this->updatePriceListsOnChildrenLevels($website, $force);
        }

        if ($currentWebsite) {
            $this->combinedPriceListGarbageCollector->cleanCombinedPriceLists();
        }
    }

    /**
     * @param Website $website
     * @param boolean $force
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, $force)
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByWebsite($website);
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection, $force);

        $relation = $this->getCombinedPriceListToWebsiteRepository()
            ->findByPrimaryKey($actualCombinedPriceList, $website);

        if (!$relation) {
            $this->connectNewPriceList($website, $actualCombinedPriceList);
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
     * @param mixed $combinedPriceListToWebsiteClassName
     */
    public function setCombinedPriceListToWebsiteClassName($combinedPriceListToWebsiteClassName)
    {
        $this->combinedPriceListToWebsiteClassName = $combinedPriceListToWebsiteClassName;
        $this->combinedPriceListToWebsiteRepository = null;
    }

    /**
     * @param mixed $priceListToWebsiteClassName
     */
    public function setPriceListToWebsiteClassName($priceListToWebsiteClassName)
    {
        $this->priceListToWebsiteClassName = $priceListToWebsiteClassName;
        $this->priceListToWebsiteRepository = null;
    }

    /**
     * @param AccountGroupCombinedPriceListsBuilder $accountGroupCombinedPriceListsBuilder
     */
    public function setAccountGroupCombinedPriceListsBuilder($accountGroupCombinedPriceListsBuilder)
    {
        $this->accountGroupCombinedPriceListsBuilder = $accountGroupCombinedPriceListsBuilder;
    }

    /**
     * @param Website $website
     * @param boolean $force
     */
    protected function updatePriceListsOnChildrenLevels(Website $website, $force)
    {
        $this->accountGroupCombinedPriceListsBuilder->build($website, null, $force);
    }

    /**
     * @param Website $website
     * @param CombinedPriceList $combinedPriceList
     */
    protected function connectNewPriceList(Website $website, CombinedPriceList $combinedPriceList)
    {
        $relation = $this->getCombinedPriceListToWebsiteRepository()->findOneBy(['website' => $website]);
        $manager = $this->registry->getManagerForClass($this->combinedPriceListToWebsiteClassName);
        if (!$relation) {
            $relation = new CombinedPriceListToWebsite();
            $relation->setPriceList($combinedPriceList);
            $relation->setWebsite($website);
            $manager->persist($relation);
        }
        $relation->setPriceList($combinedPriceList);
        $manager->flush();
    }

    /**
     * @return PriceListToWebsiteRepository
     */
    protected function getPriceListToWebsiteRepository()
    {
        if (!$this->priceListToWebsiteRepository) {
            $class = $this->priceListToWebsiteClassName;
            $this->priceListToWebsiteRepository = $this->registry->getManagerForClass($class)
                ->getRepository($class);
        }

        return $this->priceListToWebsiteRepository;
    }

    /**
     * @return PriceListToWebsiteRepository
     */
    protected function getCombinedPriceListToWebsiteRepository()
    {
        if (!$this->combinedPriceListToWebsiteRepository) {
            $class = $this->combinedPriceListToWebsiteClassName;
            $this->combinedPriceListToWebsiteRepository = $this->registry->getManagerForClass($class)
                ->getRepository($class);
        }

        return $this->combinedPriceListToWebsiteRepository;
    }
}
