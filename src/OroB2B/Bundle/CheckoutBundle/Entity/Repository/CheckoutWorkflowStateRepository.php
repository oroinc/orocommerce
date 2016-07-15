<?php
namespace OroB2B\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;

class CheckoutWorkflowStateRepository extends EntityRepository
{
    /**
     * @param string $hash
     * @param integer $entityId
     * @param string $entityClass
     * @return CheckoutWorkflowState
     */
    public function getEntityByHash($hash, $entityId, $entityClass)
    {
        return $this->createQueryBuilder('t')
            ->where('t.entityId = :entityId AND t.entityClass = :entityClass AND t.hash = :hash')
            ->setParameters([
                'entityId'    => $entityId,
                'entityClass' => $entityClass,
                'hash'        => $hash
            ])
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param integer $entityId
     * @param string $entityClass
     */
    public function deleteEntityStates($entityId, $entityClass)
    {
        $this->_em->createQueryBuilder()
            ->delete($this->_entityName, 't')
            ->where('t.entityId = :entityId and t.entityClass = :entityClass')
            ->setParameters([
                'entityId'    => $entityId,
                'entityClass' => $entityClass
            ])
            ->getQuery()
            ->execute();
    }
}
