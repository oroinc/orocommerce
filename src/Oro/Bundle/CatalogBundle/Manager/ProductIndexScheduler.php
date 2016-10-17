<?php

namespace Oro\Bundle\CatalogBundle\Manager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Receives categories that has been changed and schedule
 * reindex of products from this categories
 */
class ProductIndexScheduler
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(DoctrineHelper $doctrineHelper, EventDispatcherInterface $eventDispatcher)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Category[] $categories
     * @param int|null $websiteId
     * @param bool $isScheduled
     */
    public function scheduleProductsReindex(array $categories, $websiteId = null, $isScheduled = true)
    {
        /** @var CategoryRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Category::class);
        $productIds = $repository->getProductIdsByCategories($categories);
        $this->triggerReindexationRequestEvent($productIds, $websiteId, $isScheduled);
    }

    /**
     * @param array $productIds
     * @param int|null $websiteId
     * @param bool $isScheduled
     */
    public function triggerReindexationRequestEvent(array $productIds, $websiteId = null, $isScheduled = true)
    {
        if ($productIds) {
            $event = new ReindexationRequestEvent([Product::class], [$websiteId], $productIds, $isScheduled);
            $this->eventDispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
        }
    }
}
