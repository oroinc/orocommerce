<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;

class CheckoutDiffStorage implements CheckoutDiffStorageInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $checkoutWorkflowStateEntity;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $checkoutWorkflowStateEntity
     */
    public function __construct(DoctrineHelper $doctrineHelper, $checkoutWorkflowStateEntity)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->checkoutWorkflowStateEntity = $checkoutWorkflowStateEntity;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidEntityException
     */
    public function addState($entity, array $data)
    {
        /** @var CheckoutWorkflowState $storageEntity */
        $storageEntity = new $this->checkoutWorkflowStateEntity;
        $storageEntity->setStateData($data);
        $storageEntity->setEntityClass($this->doctrineHelper->getEntityClass($entity));
        $storageEntity->setEntityId($this->doctrineHelper->getSingleEntityIdentifier($entity));

        $em = $this->doctrineHelper->getEntityManager($storageEntity);

        $em->persist($storageEntity);
        $em->flush($storageEntity);

        return $storageEntity->getToken();
    }

    /**
     * {@inheritdoc}
     * @throws InvalidEntityException
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
     * @throws InvalidEntityException
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
        return $this->doctrineHelper->getEntityRepositoryForClass($this->checkoutWorkflowStateEntity);
    }
}
