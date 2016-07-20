<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;

class CheckoutWorkflowStateRepository extends EntityRepository
{
    /**
     * @param string $token
     * @param int $entityId
     * @param string $entityClass
     * @return CheckoutWorkflowState
     */
    public function getEntityByToken($token, $entityId, $entityClass)
    {
        $qb = $this->createQueryBuilder('t');
        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('t.entityId', ':entityId'),
                $qb->expr()->eq('t.entityClass', ':entityClass'),
                $qb->expr()->eq('t.token', ':token')
            ))
            ->setParameters([
                'entityId' => $entityId,
                'entityClass' => $entityClass,
                'token' => $token
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $entityId
     * @param string $entityClass
     */
    public function deleteEntityStates($entityId, $entityClass)
    {
        $qb = $this->createQueryBuilder('t');

        $qb
            ->delete()
            ->where($qb->expr()->andX(
                $qb->expr()->eq('t.entityId', ':entityId'),
                $qb->expr()->eq('t.entityClass', ':entityClass')
            ))
            ->setParameters([
                'entityId' => $entityId,
                'entityClass' => $entityClass
            ])
            ->getQuery()
            ->execute();
    }
}
