<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorageInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Schedule re-indexation for products by combined price lists
 */
class CombinedPriceListTriggerHandler
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Session is started when value of property > 0. Nested levels of session are supported.
     *
     * @var int
     */
    protected $isSessionStarted = 0;

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
     * @var ProductWebsiteReindexRequestDataStorageInterface
     */
    protected $websiteReindexRequestDataStorage;

    /**
     * @var int|null
     */
    protected $collectVersion = null;

    public function __construct(Registry $registry, EventDispatcherInterface $eventDispatcher)
    {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setWebsiteReindexRequestDataStorage(
        ProductWebsiteReindexRequestDataStorageInterface $websiteReindexRequestDataStorage
    ): void {
        $this->websiteReindexRequestDataStorage = $websiteReindexRequestDataStorage;
    }

    public function process(CombinedPriceList $combinedPriceList, Website $website = null)
    {
        $this->scheduleCpl[$this->getWebsiteId($website)][$combinedPriceList->getId()] = $combinedPriceList->getId();

        $this->send();
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|int[] $productIds
     * @param Website|null $website
     */
    public function processByProduct(
        CombinedPriceList $combinedPriceList,
        array $productIds = [],
        Website $website = null
    ) {
        if ($productIds) {
            $this->scheduleProductsByWebsite($productIds, $this->getWebsiteId($website));

            $this->send();
        } else {
            $this->process($combinedPriceList, $website);
        }
    }

    /**
     * Mass process changes in a set of Combined Price Lists.
     * Called by Combined Price List Garbage Collector.
     */
    public function massProcess(array $combinedPriceLists, Website $website = null)
    {
        $productIds = $this->getProductIdsByCombinedPriceLists($combinedPriceLists);
        $this->scheduleProductsByWebsite($productIds, $this->getWebsiteId($website));

        $this->send();
    }

    public function startCollect()
    {
        $this->startCollectVersioned();
    }

    public function startCollectVersioned($collectVersion = null): void
    {
        // If collect already was started with collectVersion do not override it.
        // Version will be cleared after commit or rollback in top level logic.
        if (!$this->collectVersion) {
            $this->collectVersion = $collectVersion;
        }
        $this->isSessionStarted++;
    }

    public function rollback()
    {
        if ($this->checkNestedSession()) {
            $this->clearSchedules();
        }
    }

    public function commit()
    {
        if ($this->checkNestedSession()) {
            $this->send();
        }
    }

    protected function send()
    {
        if (!$this->isSendUnlocked()) {
            return;
        }

        foreach ($this->scheduleCpl as $websiteId => $cplIds) {
            $websiteIds = $websiteId ? [$websiteId] : [];
            $this->dispatchByPriceLists($websiteIds, $cplIds);
        }

        foreach ($this->productsSchedule as $websiteId => $productIds) {
            $websiteIds = $websiteId ? [$websiteId] : [];
            $batch = array_values($productIds);
            if (null === $this->collectVersion) {
                $event = new ReindexationRequestEvent([Product::class], $websiteIds, $batch);
                $this->eventDispatcher->dispatch($event, ReindexationRequestEvent::EVENT_NAME);
            } else {
                $this->websiteReindexRequestDataStorage->insertMultipleRequests(
                    $this->collectVersion,
                    $websiteIds,
                    $batch
                );
            }
        }

        $this->clearSchedules();
    }

    /**
     * @param array|int[] $websiteIds
     * @param array|int[] $cplIds
     */
    protected function dispatchByPriceLists(array $websiteIds, array $cplIds)
    {
        // use minimal product prices because of table size
        $productIds = $this->getProductIdsByCombinedPriceLists($cplIds);

        if (!$websiteIds) {
            $this->scheduleProductsByWebsite($productIds, null);
        } else {
            foreach ($websiteIds as $websiteId) {
                $this->scheduleProductsByWebsite($productIds, $websiteId);
            }
        }
    }

    /**
     * @param array|int[] $productIds
     * @param int|null $websiteId
     */
    private function scheduleProductsByWebsite(array $productIds, $websiteId = null)
    {
        foreach ($productIds as $productId) {
            if (!isset($this->productsSchedule[null][$productId])) {
                $this->productsSchedule[$websiteId][$productId] = $productId;
            }
        }
    }

    private function isSendUnlocked(): bool
    {
        return $this->isSessionStarted === 0;
    }

    private function checkNestedSession(): bool
    {
        if ($this->isSessionStarted > 0) {
            --$this->isSessionStarted;
        }

        return $this->isSendUnlocked();
    }

    /**
     * @param Website|null $website
     * @return int|null
     */
    private function getWebsiteId(Website $website = null)
    {
        return $website?->getId();
    }

    private function clearSchedules()
    {
        $this->scheduleCpl = [];
        $this->productsSchedule = [];
        $this->collectVersion = null;
    }

    private function getProductIdsByCombinedPriceLists(array $combinedPriceLists): array
    {
        /** @var CombinedProductPriceRepository $repository */
        $repository = $this->registry->getRepository(CombinedProductPrice::class);

        return $repository->getProductIdsByPriceLists($combinedPriceLists);
    }
}
