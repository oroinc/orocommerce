<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $productPriceClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
        $objectManager = $this->doctrineHelper->getEntityManager($this->productPriceClass);

        foreach ($productPrices as $productPrice) {
            $productPriceCopy = clone $productPrice;
            $productPriceCopy->setProduct($product);
            $objectManager->persist($productPriceCopy);
        }

        $objectManager->flush();
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->productPriceClass);
    }
}
