<?php

namespace Oro\Bundle\CMSBundle\WYSIWYG;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * DTO model for entity with wysiwyg fields
 */
class WYSIWYGProcessedEntityDTO
{
    /** @var EntityManager */
    private $entityManager;

    /** @var object */
    private $entity;

    /** @var string|null */
    private $fieldName;

    /** @var string|null */
    private $fieldType;

    /** @var ClassMetadata */
    private $metadata;

    /** @var array|null */
    private $changeSet;

    /**
     * @param EntityManager $entityManager
     * @param object $entity
     * @param array|null $changeSet
     */
    public function __construct(EntityManager $entityManager, $entity, ?array $changeSet = null)
    {
        $this->entityManager = $entityManager;
        $this->entity = $entity;
        $this->changeSet = $changeSet;
    }

    /**
     * @param LifecycleEventArgs $args
     * @return WYSIWYGProcessedEntityDTO
     */
    public static function createFromLifecycleEventArgs(LifecycleEventArgs $args): WYSIWYGProcessedEntityDTO
    {
        return new self(
            $args->getEntityManager(),
            $args->getEntity(),
            $args instanceof PreUpdateEventArgs ? $args->getEntityChangeSet() : null
        );
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return int|string
     */
    public function getEntityId()
    {
        return \current($this->getMetadata()->getIdentifierValues($this->getEntity()));
    }

    /**
     * @return ClassMetadata
     */
    public function getMetadata(): ClassMetadata
    {
        if (!$this->metadata) {
            $entity = $this->getEntity();
            if ($entity instanceof Collection) {
                $entity = $entity->first();
            }

            $this->metadata = $this->getEntityManager()->getClassMetadata(\get_class($entity));
        }

        return $this->metadata;
    }

    /**
     * @return string|null
     */
    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    /**
     * @return string|null
     */
    public function getFieldType(): ?string
    {
        return $this->fieldType;
    }

    /**
     * @param string $fieldName
     * @param string|null $fieldType Null when field is relation to children entity
     * @return WYSIWYGProcessedEntityDTO
     */
    public function withField(string $fieldName, ?string $fieldType = null): WYSIWYGProcessedEntityDTO
    {
        $dto = clone $this;
        $dto->fieldName = $fieldName;
        $dto->fieldType = $fieldType;

        return $dto;
    }

    /**
     * @return mixed
     */
    public function getFieldValue()
    {
        return $this->getMetadata()->getFieldValue($this->getEntity(), $this->getFieldName());
    }

    /**
     * @return bool
     */
    public function isFieldChanged(): bool
    {
        return $this->changeSet === null || array_key_exists($this->getFieldName(), $this->changeSet);
    }

    /**
     * @return bool
     */
    public function isRelation(): bool
    {
        return $this->getMetadata()->hasAssociation($this->getFieldName());
    }
}
