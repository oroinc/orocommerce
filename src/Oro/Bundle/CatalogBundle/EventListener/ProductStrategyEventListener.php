<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

/**
 * Set a category to the product after product import processed.
 */
class ProductStrategyEventListener extends AbstractProductImportEventListener
{
    public function onProcessAfter(ProductStrategyEvent $event)
    {
        $rawData = $event->getRawData();
        if (empty($rawData[self::CATEGORY_KEY])) {
            return;
        }

        $product = $event->getProduct();

        $category = $this->getCategoryByDefaultTitle($rawData[self::CATEGORY_KEY]);

        if ($category) {
            $product->setCategory($category);
        }
    }
}
