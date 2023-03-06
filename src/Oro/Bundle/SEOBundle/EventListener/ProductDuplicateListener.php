<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Clone meta fields and link them with duplicate product
 */
class ProductDuplicateListener
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var array */
    private $metaFields = [];

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(PropertyAccessorInterface $propertyAccessor, array $metaFields = [])
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->metaFields = $metaFields;
    }

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Link new product with cloned meta fields from source product
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
