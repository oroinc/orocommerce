<?php

namespace OroB2B\Bundle\RFPBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class RequestStatusRepository extends EntityRepository
{
    /**
     * Returns all statuses that are not deleted
     *
     * @return RequestStatus[]
     */
    public function getNotDeletedStatuses()
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('requestStatus')
            ->from('OroB2BRFPBundle:RequestStatus', 'requestStatus')
            ->where('requestStatus.deleted = 0')
            ->orderBy('requestStatus.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
