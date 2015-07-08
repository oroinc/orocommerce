<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
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
     * @var SkuIncrementor
     */
    protected $skuIncrementor;

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
     * @param SkuIncrementor $skuIncrementor
     */
    public function setSkuIncrementor($skuIncrementor)
    {
        $this->skuIncrementor = $skuIncrementor;
    }

    /**
     * @param Product $product
     * @return Product
     */
    public function duplicate(Product $product)
    {
        $productCopy = clone $product;

        $productCopy->setSku($this->skuIncrementor->increment($product->getSku()));

        $productCopy->setStatus($this->getDisabledStatus());

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

    /**
     * @return null|AbstractEnumValue
     */
    private function getDisabledStatus()
    {
        $className = ExtendHelper::buildEnumValueClassName('prod_status');
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $this->objectManager->getRepository($className);

        return $enumRepo->find(Product::STATUS_DISABLED);
    }
}
