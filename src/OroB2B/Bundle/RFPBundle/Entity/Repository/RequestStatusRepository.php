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
            ->getNotDeletedRequestStatusesQueryBuilder()
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Returns all statuses that are not deleted and deleted statuse that have requests
     *
     * @return \OroB2B\Bundle\RFPBundle\Entity\RequestStatus[]
     */
    public function getNotDeletedAndDeletedWithRequestsStatuses()
    {
        return $this->getNotDeletedRequestStatusesQueryBuilder()
            ->orWhere('requestStatus.deleted = :deleted_param AND request.id IS NOT NULL')
            ->setParameter('deleted_param', true, \PDO::PARAM_BOOL)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Returns QB
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getNotDeletedRequestStatusesQueryBuilder()
    {
        return $this
            ->createQueryBuilder('requestStatus')
            ->orderBy('requestStatus.sortOrder', 'ASC')
            ->leftJoin('OroB2BRFPBundle:Request', 'request', Join::WITH, 'IDENTITY(request.status) = requestStatus.id')
            ->where('requestStatus.deleted = :not_deleted_param')
            ->setParameter('not_deleted_param', false, \PDO::PARAM_BOOL)
        ;
    }
}
