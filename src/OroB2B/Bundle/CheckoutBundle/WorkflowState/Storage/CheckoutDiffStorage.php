<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;

class CheckoutDiffStorage implements CheckoutDiffStorageInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(EntityManager $entityManager, DoctrineHelper $doctrineHelper)
    {
        $this->entityManager = $entityManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     * @throws \Oro\Bundle\EntityBundle\Exception\InvalidEntityException
     */
    public function addState($entity, array $data)
    {
        /** @var CheckoutWorkflowState $storageEntity */
        $storageEntity = new CheckoutWorkflowState();
        $storageEntity->setStateData($data);
        $storageEntity->setEntityClass($this->doctrineHelper->getEntityClass($entity));
        $storageEntity->setEntityId($this->doctrineHelper->getSingleEntityIdentifier($entity));

        $this->entityManager->persist($storageEntity);
        $this->entityManager->flush($storageEntity);

        return $storageEntity->getToken();
    }

    /**
     * {@inheritdoc}
     * @throws \Oro\Bundle\EntityBundle\Exception\InvalidEntityException
     */
    public function readState($entity, $token)
    {
        $storageEntity = $this->getRepository()->getEntityByToken(
            $this->doctrineHelper->getSingleEntityIdentifier($entity),
            $this->doctrineHelper->getEntityClass($entity),
            $token
        );

        return (null === $storageEntity) ? [] : $storageEntity->getStateData();
    }

    /**
     * {@inheritdoc}
     * @throws \Oro\Bundle\EntityBundle\Exception\InvalidEntityException
     */
    public function deleteStates($entity)
    {
        $this
            ->getRepository()
            ->deleteEntityStates(
                $this->doctrineHelper->getSingleEntityIdentifier($entity),
                $this->doctrineHelper->getEntityClass($entity)
            );
    }

    /**
     * @return CheckoutWorkflowStateRepository
     */
    protected function getRepository()
    {
        return $this->entityManager->getRepository('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState');
    }
}
