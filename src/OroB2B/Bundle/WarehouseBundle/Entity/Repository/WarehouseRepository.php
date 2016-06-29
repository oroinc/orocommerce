<?php

namespace OroB2B\Bundle\WarehouseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseRepository extends EntityRepository
{
    /**
     * Returns the number of the warehouses found in the system
     *
     * @return integer
     */
    public function countWarehouses()
    {
        $queryBuilder = $this->createQueryBuilder('w')->select('count(w.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Checks if we have warehouses in the system. If there is no warehouse found than throws
     * and exception. Otherwise it will return the first warehouse
     *
     * @return null|Warehouse
     */
    public function getSingularWarehouse()
    {
        $queryBuilder = $this->createQueryBuilder('w')
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
