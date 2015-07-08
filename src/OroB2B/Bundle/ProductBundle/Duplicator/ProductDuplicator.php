<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicator
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var SkuIncrementorInterface
     */
    protected $skuIncrementor;

    /**
     * @param ObjectManager $objectManager
     * @param EventDispatcher $eventDispatcher
     * @param SkuIncrementorInterface $skuIncrementor
     */
    public function __construct(
        ObjectManager $objectManager,
        EventDispatcher $eventDispatcher,
        SkuIncrementorInterface $skuIncrementor
    ) {
        $this->objectManager = $objectManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->skuIncrementor = $skuIncrementor;
    }

    /**
     * @param Product $product
     * @return Product
     */
    public function duplicate(Product $product)
    {
        $productCopy = $this->createProductCopy($product);

        $this->objectManager->persist($productCopy);
        $this->objectManager->flush($productCopy);

        $this->eventDispatcher->dispatch(
            ProductDuplicateAfterEvent::NAME,
            new ProductDuplicateAfterEvent($productCopy, $product)
        );

        return $productCopy;
    }

    /**
     * @param Product $product
     * @return Product
     */
    private function createProductCopy(Product $product)
    {
        $productCopy = clone $product;

        $productCopy->setSku($this->skuIncrementor->increment($product->getSku()));
        $productCopy->setStatus($this->getDisabledStatus());

        $this->cloneChildObjects($product, $productCopy);

        return $productCopy;
    }

    /**
     * @param Product $product
     * @param Product $productCopy
     */
    private function cloneChildObjects(Product $product, Product $productCopy)
    {
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            $productCopy->addUnitPrecision(clone $unitPrecision);
        }

        /**
         * @TODO need to copy file itself
         */
        if ($image = $product->getImage()) {
            $productCopy->setImage(clone $image);
        }

        /**
         * @TODO need to copy attachment
         */
    }

    /**
     * @return AbstractEnumValue
     */
    private function getDisabledStatus()
    {
        $className = ExtendHelper::buildEnumValueClassName('prod_status');

        return $this->objectManager->getRepository($className)->find(Product::STATUS_DISABLED);
    }
}
