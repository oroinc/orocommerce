<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Specification;

use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

/**
 * This trait helps to get entity data before the modification, it could be used to get original entity data
 * after it was loaded from the DB to the UOW include situation when we are in the middle of the flush operation
 */
trait OriginalDataAccessorTrait
{
    /**
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * @param object $entity
     *
     * @return array
     */
    private function getOriginalEntityData($entity)
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

        return $originalEntityData;
    }
}
