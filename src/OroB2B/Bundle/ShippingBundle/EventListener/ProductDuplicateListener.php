<?php

namespace Oro\Bundle\ShippingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;

class ProductDuplicateListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $productShippingOptionsClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $productShippingOptionsClass
     */
    public function setProductShippingOptionsClass($productShippingOptionsClass)
    {
        $this->productShippingOptionsClass = $productShippingOptionsClass;
    }

    /**
     * @param ProductDuplicateAfterEvent $event
     */
    public function onDuplicateAfter(ProductDuplicateAfterEvent $event)
    {
        $product = $event->getProduct();
        $sourceProduct = $event->getSourceProduct();

        $repository = $this->doctrineHelper->getEntityRepository($this->productShippingOptionsClass);
        $manager = $this->doctrineHelper->getEntityManager($this->productShippingOptionsClass);

        /** @var ProductShippingOptions[] $options */
        $options = $repository->findBy(['product' => $sourceProduct]);
        if (count($options)) {
            foreach ($options as $option) {
                $entity = clone $option;
                $entity->setProduct($product);

                $manager->persist($entity);
            }

            $manager->flush();
        }
    }
}
