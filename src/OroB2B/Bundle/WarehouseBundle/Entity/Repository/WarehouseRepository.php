<?php

namespace OroB2B\Bundle\WarehouseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

class WarehouseRepository extends EntityRepository
{
    public function getWarehouseCount()
    {
        $queryBuilder = $this->createQueryBuilder('w')
            ->select('count(w.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getSingularWarehouse()
    {
        $warehouses = $this->findAll();

        if (count($warehouses) < 1) {
            throw new LogicException('Expecting at least one warehouse in the system and found none.');
        }

        return $warehouses[0];
    }
}
