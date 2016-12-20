<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

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

        $categoryDefaultTitle = $rawData[self::CATEGORY_KEY];
        $product = $event->getProduct();

        if ($product->getId()) {
            $category = $this->getCategoryByProduct($product);
            if ($category) {
                $category->removeProduct($product);
            }
        }

        $category = $this->getCategoryByDefaultTitle($categoryDefaultTitle);
        if ($category) {
            $category->addProduct($product);
        }
    }
}
