<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Specification;

use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\OrderHolderInterface;

/**
 * This trait helps to get entity data before the modification, it could be used to get original entity data
 * after it was loaded from the DB to the UOW include situation when we are in the middle of the flush operation
 */
trait OriginalDataAccessorTrait
{
    private UnitOfWork $unitOfWork;

    private function getOriginalEntityData(object $entity): array
    {
        $originalEntityData = $this->unitOfWork->getOriginalEntityData($entity);

        /**
         * In case if called in the middle of the flush operation,
         * getOriginalEntityData will return new data instead of the original data
         * (an example on the onFlush event). So we need to check entity changeSet instead of compare
         * the original data
         */
        $changeSet = $this->unitOfWork->getEntityChangeSet($entity);
        foreach ($changeSet as $property => $values) {
            if ($originalEntityData[$property] instanceof PersistentCollection) {
                $originalEntityData[$property] = $values;
            } else {
                $originalEntityData[$property] = $values[0];
            }
        }

        return $originalEntityData ?? [];
    }

    private function isPriceChanged(OrderHolderInterface $lineItem, array $originalData): bool
    {
        $newPrice = $lineItem->getPrice()
            ? $lineItem->getPrice()->getValue()
            : $lineItem->getValue();

        $originalPrice = $originalData['value'] ?? null;
        /**
         * Comparison should not be strict because value in `$originalOrderData` could be a string and
         * `$orderLineItem->getPrice()->getValue()` will return float
         */
        return $newPrice != $originalPrice;
    }

    private function isQuantityChanged(OrderHolderInterface $lineItem, array $originalData): bool
    {
        $originalQuantity = $originalData['quantity'] ?? null;

        return $lineItem->getQuantity() != $originalQuantity;
    }
}
