<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\MinimalProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CombinedPriceListTriggerHandler
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var bool
     */
    protected $isSessionStarted;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $scheduleCpl = [];

    /**
     * @var array
     */
    protected $productsSchedule = [];

    /**
     * CombinedPriceListTriggerHandler constructor.
     * @param Registry $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(Registry $registry, EventDispatcherInterface $eventDispatcher)
    {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Website $website
     */
    public function process(CombinedPriceList $combinedPriceList, Website $website = null)
    {
        $websiteId = $website ? $website->getId() : null;
        $this->scheduleCpl[$websiteId][$combinedPriceList->getId()] = $combinedPriceList->getId();

        if (!$this->isSessionStarted) {
            $this->send();
        }
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product|null $product
     * @param Website|null $website
     */
    public function processByProduct(
        CombinedPriceList $combinedPriceList,
        Product $product = null,
        Website $website = null
    ) {
        if ($product) {
            $websiteId = $website ? $website->getId() : null;
            $this->productsSchedule[$websiteId][$product->getId()] = $product->getId();
            if (!$this->isSessionStarted) {
                $this->send();
            }
        } else {
            $this->process($combinedPriceList, $website);
        }
    }

    /**
     * @param array $combinedPriceLists
     * @param Website|null $website
     */
    public function massProcess(array $combinedPriceLists, Website $website = null)
    {
        $websiteId = $website ? $website->getId() : null;

        // use minimal product prices because of table size
        $repository = $this->registry->getManagerForClass(MinimalProductPrice::class)
            ->getRepository(MinimalProductPrice::class);
        $productIds = $repository->getProductIdsByPriceLists($combinedPriceLists);
        $this->productsSchedule[$websiteId] = $productIds;

        if (!$this->isSessionStarted) {
            $this->send();
        }
    }

    public function startCollect()
    {
        $this->isSessionStarted = true;
    }

    public function rollback()
    {
        $this->scheduleCpl = [];
        $this->productsSchedule = [];
        $this->isSessionStarted = false;
    }

    public function commit()
    {
        $this->isSessionStarted = false;
        $this->send();
    }

    protected function send()
    {
        foreach ($this->scheduleCpl as $websiteId => $cplIds) {
            $websiteIds = $websiteId ? [$websiteId] : [];
            $this->dispatchByPriceLists($websiteIds, $cplIds);
        }
        foreach ($this->productsSchedule as $websiteId => $productIds) {
            $websiteIds = $websiteId ? [$websiteId] : [];
            $event = new ReindexationRequestEvent([Product::class], $websiteIds, array_values($productIds));
            $this->eventDispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
        }


        $this->scheduleCpl = [];
        $this->productsSchedule = [];
    }

    /**
     * @param array $websiteIds
     * @param array $cplIds
     */
    protected function dispatchByPriceLists(array $websiteIds, array $cplIds)
    {
        // use minimal product prices because of table size
        $repository = $this->registry->getManagerForClass(MinimalProductPrice::class)
            ->getRepository(MinimalProductPrice::class);
        $productIds = $repository->getProductIdsByPriceLists($cplIds);
        if ($productIds) {
            $event = new ReindexationRequestEvent([Product::class], $websiteIds, $productIds);
            $this->eventDispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
        }
    }
}
