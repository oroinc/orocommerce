<?php

namespace OroB2B\Bundle\RFPBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestStatusRepository extends EntityRepository
{
    /**
     * Returns all statuses that are not deleted
     *
     * @return RequestStatus[]
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
     * @return RequestStatus[]
     */
    public function getNotDeletedAndDeletedWithRequestsStatuses()
    {
        $qb = $this->getNotDeletedRequestStatusesQueryBuilder();

        return $qb
            ->leftJoin(
                'OroB2BRFPBundle:Request',
                'request',
                Join::WITH,
                $qb->expr()->eq('IDENTITY(request.status)', 'requestStatus.id')
            )
            ->orWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('requestStatus.deleted', ':deleted_param'),
                    $qb->expr()->isNotNull('request.id')
                )
            )
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
        $qb = $this->createQueryBuilder('requestStatus');

        return $qb
            ->orderBy($qb->expr()->asc('requestStatus.sortOrder'))
            ->where($qb->expr()->eq('requestStatus.deleted', ':not_deleted_param'))
            ->setParameter('not_deleted_param', false, \PDO::PARAM_BOOL)
        ;
    }
}
