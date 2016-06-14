<?php

namespace OroB2B\Bundle\WarehouseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class WarehouseRepository extends EntityRepository
{
    public function warehouseCount()
    {
        $queryBuilder = $this->createQueryBuilder('w')
            ->select('count(w.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
