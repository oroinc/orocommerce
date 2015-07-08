<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListener
{
    /**
     * @var OroEntityManager
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $productPriceClass;

    /**
     * @param OroEntityManager $objectManager
     */
    public function setObjectManager(OroEntityManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $productPriceClass
     */
    public function setProductPriceClass($productPriceClass)
    {
        $this->productPriceClass = $productPriceClass;
    }

    /**
     * Copy product prices
     *
     * @param ProductDuplicateAfterEvent $event
     */
    public function onDuplicateAfter(ProductDuplicateAfterEvent $event)
    {
        $product = $event->getProduct();
        $sourceProduct = $event->getSourceProduct();

        $productPrices = $this->getProductPriceRepository()->getPricesByProduct($sourceProduct);

        foreach ($productPrices as $productPrice) {
            $productPriceCopy = clone $productPrice;
            $productPriceCopy->setProduct($product);
            $this->objectManager->persist($productPriceCopy);
        }

        $this->objectManager->flush();
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->objectManager->getRepository($this->productPriceClass);
    }
}
