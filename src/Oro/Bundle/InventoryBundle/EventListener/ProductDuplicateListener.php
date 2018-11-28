<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Clone inventory fields and link them with duplicate product
 */
class ProductDuplicateListener
{
    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var array */
    private $fields = [];

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param array $fields
     */
    public function __construct(PropertyAccessor $propertyAccessor, array $fields = [])
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->fields = $fields;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Link new product with cloned inventory fields from source product
     *
     * @param ProductDuplicateAfterEvent $event
     */
    public function onDuplicateAfter(ProductDuplicateAfterEvent $event)
    {
        $product = $event->getProduct();
        $sourceProduct = $event->getSourceProduct();

        foreach ($this->fields as $field) {
            /** @var Collection $collection */
            $originValue = $this->propertyAccessor->getValue($sourceProduct, $field);
            if ($originValue) {
                $this->propertyAccessor->setValue($product, $field, clone $originValue);
            }
        }

        $em = $this->doctrineHelper->getEntityManager(get_class($product));
        $em->flush($product);
    }
}
