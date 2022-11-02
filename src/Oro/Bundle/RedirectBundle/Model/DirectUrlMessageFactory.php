<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;

/**
 * Message factory for interaction with Direct URL related MQ message data.
 */
class DirectUrlMessageFactory implements MessageFactoryInterface
{
    public const ID = 'id';
    public const ENTITY_CLASS_NAME = 'class';
    public const CREATE_REDIRECT = 'createRedirect';

    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage(SluggableInterface $entity): array
    {
        $createRedirect = true;
        if ($entity->getSlugPrototypesWithRedirect()) {
            $createRedirect = $entity->getSlugPrototypesWithRedirect()->getCreateRedirect();
        }

        return [
            self::ID => $entity->getId(),
            self::ENTITY_CLASS_NAME => ClassUtils::getClass($entity),
            self::CREATE_REDIRECT => $createRedirect,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createMassMessage($entityClass, $id, $createRedirect = true): array
    {
        return [
            self::ID => $id,
            self::ENTITY_CLASS_NAME => $entityClass,
            self::CREATE_REDIRECT => $createRedirect,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntitiesFromMessage($data): array
    {
        $className = $data[self::ENTITY_CLASS_NAME];

        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManagerForClass($className);
        $metadata = $em->getClassMetadata($className);
        $idFieldName = $metadata->getSingleIdentifierFieldName();

        return $em->getRepository($className)
            ->findBy([$idFieldName => $data[self::ID]]);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassFromMessage($data)
    {
        return $data[self::ENTITY_CLASS_NAME];
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateRedirectFromMessage($data)
    {
        return $data[self::CREATE_REDIRECT];
    }
}
