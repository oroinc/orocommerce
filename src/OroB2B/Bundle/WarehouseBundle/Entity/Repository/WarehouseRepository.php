<?php

namespace OroB2B\Bundle\WarehouseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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
     * Returns the firtst warehouse in the system or null if there are no warehouses
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
