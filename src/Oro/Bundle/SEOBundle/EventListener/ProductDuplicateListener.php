<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductDuplicateListener
{
    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var array */
    private $metaFields = [];

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param array $metaFields
     */
    public function __construct(PropertyAccessor $propertyAccessor, array $metaFields = [])
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->metaFields = $metaFields;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Link new product with category from source product
     *
     * @param ProductDuplicateAfterEvent $event
     */
    public function onDuplicateAfter(ProductDuplicateAfterEvent $event)
    {
        $product = $event->getProduct();
        $sourceProduct = $event->getSourceProduct();

        foreach ($this->metaFields as $metaField) {
            /** @var Collection $collection */
            $collection = $this->propertyAccessor->getValue($sourceProduct, $metaField);
            $newCollection = new ArrayCollection();

            foreach ($collection as $field) {
                $newCollection->add(clone $field);
            }

            $this->propertyAccessor->setValue($product, $metaField, $newCollection);
        }

        $em = $this->doctrineHelper->getEntityManager(get_class($product));
        $em->flush($product);
    }
}
