<?php

namespace OroB2B\Bundle\WarehouseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseRepository extends EntityRepository
{
    /**
     * Returns the first warehouse in the system or null if there are no warehouses
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

    /**
     * Counts all entities in current repository.
     * @return integer
     */
    public function countAll()
    {
        try {
            $result = $this->createQueryBuilder('entity')
                ->select('COUNT(entity)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        } catch (NoResultException $e) {
            return 0;
        }

        return (int)$result;
    }
}
