<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\Exception\NotIntegerPrimaryKeyEntityException;

class CheckoutDiffStorage implements CheckoutDiffStorageInterface
{
    /** @var string */
    private static $storageEntityClass = 'OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState';

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
        $this->entityManager  = $entityManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     * @throws NotIntegerPrimaryKeyEntityException
     */
    public function addState($entity, array $data)
    {
        /** @var CheckoutWorkflowState $storageEntity */
        $storageEntity = new self::$storageEntityClass;

        $hash = uniqid('', false);
        $storageEntity->setHash($hash);
        $storageEntity->setStateData($data);
        $storageEntity->setEntityClass(get_class($entity));
        $storageEntity->setEntityId($this->getEntityId($entity));

        $this->entityManager->persist($storageEntity);
        $this->entityManager->flush($storageEntity);

        return $hash;
    }

    /**
     * {@inheritdoc}
     * @throws NotIntegerPrimaryKeyEntityException
     */
    public function readState($entity, $hash)
    {
        $storageEntity = $this->getRepository()->getEntityByHash(
            $this->getEntityId($entity),
            get_class($entity),
            $hash
        );

        return (null === $storageEntity) ? [] : $storageEntity->getStateData();
    }

    /**
     * {@inheritdoc}
     * @throws NotIntegerPrimaryKeyEntityException
     */
    public function deleteStates($entity)
    {
        $this
            ->getRepository()
            ->deleteEntityStates($this->getEntityId($entity), get_class($entity));
    }

    /**
     * @return CheckoutWorkflowStateRepository
     */
    protected function getRepository()
    {
        return $this->entityManager->getRepository(self::$storageEntityClass);
    }

    /**
     * @param object $entity
     * @return integer
     * @throws NotIntegerPrimaryKeyEntityException
     */
    protected function getEntityId($entity)
    {
        $identifiers = $this->doctrineHelper->getEntityIdentifier($entity);
        $identifier  = reset($identifiers);
        if (!is_int($identifier) || count($identifiers) > 1) {
            throw new NotIntegerPrimaryKeyEntityException('Entity must have integer primary key');
        }

        return $identifier;
    }
}
