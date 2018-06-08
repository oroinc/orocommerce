<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

/**
 * Set a category to the product after product import processed.
 */
class ProductStrategyEventListener extends AbstractProductImportEventListener
{
    /**
     * @param ProductStrategyEvent $event
     */
    public function onProcessAfter(ProductStrategyEvent $event)
    {
        $rawData = $event->getRawData();
        if (empty($rawData[self::CATEGORY_KEY])) {
            return;
        }

        $category = $this->getCategoryByDefaultTitle($rawData[self::CATEGORY_KEY]);
        $product = $event->getProduct();

        if ($category) {
            $product->setCategory($category);
        }
    }

    /**
     * @deprecated, is left out of considerations of backward compatibility and will be removed in 3.0.
     *
     * @param PreFlushEventArgs $event
     */
    public function preFlush(PreFlushEventArgs $event)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onClear()
    {
        parent::onClear();
    }
}
