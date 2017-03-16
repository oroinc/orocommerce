<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

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
     * @var QueryHintResolverInterface
     */
    protected $hintResolver;

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

        $productPrices = $this->getProductPriceRepository()->getPricesByProduct($this->hintResolver, $sourceProduct);
        $objectManager = $this->doctrineHelper->getEntityManager($this->productPriceClass);

        foreach ($productPrices as $productPrice) {
            $productPriceCopy = clone $productPrice;
            $productPriceCopy->setProduct($product);
            $this->getProductPriceRepository()->persist($this->hintResolver, $productPriceCopy);
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

    /**
     * @param QueryHintResolverInterface $hintResolver
     */
    public function setHintResolver(QueryHintResolverInterface $hintResolver)
    {
        $this->hintResolver = $hintResolver;
    }
}
