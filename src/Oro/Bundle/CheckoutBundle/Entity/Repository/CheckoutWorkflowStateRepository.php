<?php

namespace Oro\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;

class CheckoutWorkflowStateRepository extends EntityRepository
{
    /**
     * @param int $entityId
     * @param string $entityClass
     * @param string $token
     * @return CheckoutWorkflowState|null
     */
    public function getEntityByToken($entityId, $entityClass, $token)
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
     * @param string|null $token
     */
    public function deleteEntityStates($entityId, $entityClass, $token = null)
    {
        $qb = $this->createQueryBuilder('t');

        $qb
            ->delete()
            ->where($qb->expr()->andX(
                $qb->expr()->eq('t.entityId', ':entityId'),
                $qb->expr()->eq('t.entityClass', ':entityClass')
            ))
            ->setParameters([
                'entityId' => (int)$entityId,
                'entityClass' => $entityClass
            ]);


        if ($token) {
            $qb
                ->andWhere($qb->expr()->eq('t.token', ':token'))
                ->setParameter('token', $token);
        }

        $qb
            ->getQuery()
            ->execute();
    }
}
