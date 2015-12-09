<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

class ProductStrategyEventListener extends AbstractProductImportEventListener
{
    /** @var Category[] */
    protected $categories = [];

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

        $category = $this->getCategoryByDefaultTitle($categoryDefaultTitle);
        if ($category) {
            $category->addProduct($product);

            return;
        }

        if ($product->getId()) {
            $category = $this->getCategoryByProduct($product);
            if ($category) {
                $category->removeProduct($product);
            }
        }
    }
}
