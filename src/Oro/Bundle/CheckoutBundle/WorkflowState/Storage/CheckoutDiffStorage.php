<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Storage;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;

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
    public function addState($entity, array $data, $options = [])
    {
        /** @var CheckoutWorkflowState $storageEntity */
        $storageEntity = new $this->checkoutWorkflowStateEntity;
        $storageEntity->setStateData($data);
        $storageEntity->setEntityClass($this->doctrineHelper->getEntityClass($entity));
        $storageEntity->setEntityId($this->doctrineHelper->getSingleEntityIdentifier($entity));

        if (isset($options['token'])) {
            $storageEntity->setToken($options['token']);
        }

        $this->saveStorageEntity($storageEntity);

        return $storageEntity->getToken();
    }

    /**
     * {@inheritdoc}
     * @throws InvalidEntityException
     */
    public function getState($entity, $token)
    {
        $storageEntity = $this->getStorageEntity($entity, $token);

        return $storageEntity ? $storageEntity->getStateData() : [];
    }

    /**
     * {@inheritdoc}
     * @throws InvalidEntityException
     */
    public function deleteStates($entity, $token = null)
    {
        $this
            ->getRepository()
            ->deleteEntityStates(
                $this->doctrineHelper->getSingleEntityIdentifier($entity),
                $this->doctrineHelper->getEntityClass($entity),
                $token
            );
    }

    /**
     * @param object $entity
     * @param string $token
     * @return null|CheckoutWorkflowState
     */
    protected function getStorageEntity($entity, $token)
    {
        return $this->getRepository()->getEntityByToken(
            $this->doctrineHelper->getSingleEntityIdentifier($entity),
            $this->doctrineHelper->getEntityClass($entity),
            $token
        );
    }

    /**
     * @param CheckoutWorkflowState $storageEntity
     */
    protected function saveStorageEntity($storageEntity)
    {
        $em = $this->doctrineHelper->getEntityManager($storageEntity);

        $em->persist($storageEntity);
        $em->flush($storageEntity);
    }

    /**
     * @return CheckoutWorkflowStateRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass($this->checkoutWorkflowStateEntity);
    }
}
