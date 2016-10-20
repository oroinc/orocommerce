<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\CatalogBundle\Model\CategoryMessageHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductMessageHandler;

class CategoryListener
{
    const FIELD_PRODUCTS = 'products';

    /**
     * @var CategoryMessageHandler
     */
    protected $categoryMessageHandler;

    /**
     * @param ProductMessageHandler $productMessageHandler
     */
    public function __construct(ProductMessageHandler $productMessageHandler)
    {
        $this->productMessageHandler = $productMessageHandler;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->handleProductsChange($event);
    }

    /**
     * @param OnFlushEventArgs $event
     */
    protected function handleProductsChange(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $collections = $unitOfWork->getScheduledCollectionUpdates();
        foreach ($collections as $collection) {
            if ($collection instanceof PersistentCollection
                && $collection->getMapping()['fieldName'] === self::FIELD_PRODUCTS
                && $collection->isDirty() && $collection->isInitialized()
            ) {
                /** @var Product $product */
                foreach (array_merge($collection->getInsertDiff(), $collection->getDeleteDiff()) as $product) {
                    // Message should be send only for already existing products
                    // New products has own queue message for visibility calculation
                    if ($product->getId()) {
                        $this->productMessageHandler->addProductMessageToSchedule(
                            'oro_customer.visibility.change_product_category',
                            $product
                        );
                    }
                }
            }
        }
    }
}
