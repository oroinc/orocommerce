<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Handler for CollectionSortOrder entities
 */
class CollectionSortOrderHandler
{
    public function __construct(protected DoctrineHelper $doctrineHelper)
    {
    }

    /**
     * @param array $sortOrdersToUpdate
     * @return void
     */
    public function updateCollections(array $sortOrdersToUpdate)
    {
        foreach ($sortOrdersToUpdate as $updateData) {
            if (!empty($updateData['sortOrders'])) {
                $this->updateSegmentSortOrders($updateData['sortOrders'], $updateData['segment']);
            }
        }
        $this->getManager()->flush();
    }

    /**
     * @param CollectionSortOrder[] $sortOrders
     * @param Segment $segment
     * @param bool $forceFlush
     * @return void
     */
    public function updateSegmentSortOrders(array $sortOrders, Segment $segment, bool $forceFlush = false): void
    {
        foreach ($sortOrders as $sortOrder) {
            // Managing empty values inputted (null) : we remove the line if entity exists or ignore it if it doesn't
            if (is_null($sortOrder->getSortOrder())) {
                if (is_null($sortOrder->getId())) {
                    continue;
                }
                $this->getManager()->remove($sortOrder);
            } else {
                // Managing the case of a new Segment, must be reinjected
                $sortOrder->setSegment($segment);
                // Saving
                $this->getManager()->persist($sortOrder);
            }
        }
        if ($forceFlush) {
            $this->getManager()->flush();
        }
    }

    /**
     * @param Product $product
     * @param Segment $segment
     * @return CollectionSortOrder|null
     */
    public function getCollectionSortOrderByUnicity(Product $product, Segment $segment): ?CollectionSortOrder
    {
        return $this->getRepository()->findOneBy([
            'product' => $product,
            'segment' => $segment
        ]);
    }

    /**
     * @return EntityRepository
     */
    private function getRepository(): EntityRepository
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(CollectionSortOrder::class);
    }

    /**
     * @return EntityManager
     */
    private function getManager(): EntityManager
    {
        return $this->doctrineHelper->getEntityManager(CollectionSortOrder::class);
    }
}
