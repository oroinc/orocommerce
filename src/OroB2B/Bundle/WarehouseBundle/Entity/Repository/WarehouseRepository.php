<?php

namespace OroB2B\Bundle\WarehouseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class WarehouseRepository extends EntityRepository
{
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
