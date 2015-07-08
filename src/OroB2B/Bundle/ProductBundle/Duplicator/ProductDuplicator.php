<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\EventDispatcher\EventDispatcher;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicator
{
    /** @var  ObjectManager */
    protected $objectManager;

    /** @var  EventDispatcher */
    protected $eventDispatcher;

    /**
     * @param ObjectManager $objectManager
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Product $product
     * @return Product
     */
    public function duplicate(Product $product)
    {
        $productCopy = clone $product;

        /**
         * @TODO Replace with SkuIncrementor
         */
        $productCopy->setSku($productCopy->getSku() . '-' . time());

        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            $unitPrecisionCopy = clone $unitPrecision;
            $productCopy->addUnitPrecision($unitPrecisionCopy);
        }

        /**
         * @TODO need to copy file itself
         */
        $productCopy->setImage(clone $product->getImage());

        /**
         * @TODO need to copy attachment
         */

        $this->objectManager->persist($productCopy);
        $this->objectManager->flush($productCopy);

        $this->eventDispatcher->dispatch(
            ProductDuplicateAfterEvent::NAME,
            new ProductDuplicateAfterEvent($productCopy, $product)
        );

        return $productCopy;
    }
}
