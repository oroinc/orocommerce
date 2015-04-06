<?php

namespace OroB2B\Bundle\RFPBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class RequestStatusRepository extends EntityRepository
{
    /**
     * Returns all statuses that are not deleted
     *
     * @return \OroB2B\Bundle\RFPBundle\Entity\RequestStatus[]
     */
    public function getNotDeletedStatuses()
    {
        return $this
            ->createQueryBuilder('requestStatus')
            ->where('requestStatus.deleted = ?1')
            ->setParameter(1, false, \PDO::PARAM_BOOL)
            ->orderBy('requestStatus.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
