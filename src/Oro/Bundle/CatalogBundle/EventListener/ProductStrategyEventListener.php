<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

class ProductStrategyEventListener extends AbstractProductImportEventListener
{
    private $productsToAdd = [];

    private $flushInProgress = false;

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
            $this->productsToAdd[] = [$category, $product];
        }
    }

    /**
     * IMPORTANT: It's a workaround for doctrine2 bug
     * @see https://github.com/doctrine/doctrine2/issues/6186
     * @see BB-5999
     * @param PreFlushEventArgs $event
     */
    public function preFlush(PreFlushEventArgs $event)
    {
        if (!$this->productsToAdd || $this->flushInProgress) {
            return;
        }

        $em = $event->getEntityManager();

        $this->flushInProgress = true;
        $em->flush();
        $this->flushInProgress = false;

        /**
         * @var Category $category
         * @var Product $product
         */
        foreach ($this->productsToAdd as list($category, $product)) {
            if ($em->contains($category) && $em->contains($product)) {
                $category->addProduct($product);
            }
        }

        $this->productsToAdd = [];
    }

    /**
     * {@inheritdoc}
     */
    public function onClear()
    {
        $this->productsToAdd = [];
        parent::onClear();
    }
}
