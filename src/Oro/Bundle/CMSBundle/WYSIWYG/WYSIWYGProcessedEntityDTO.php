<?php

namespace Oro\Bundle\CMSBundle\WYSIWYG;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * DTO model for entity with wysiwyg fields
 */
class WYSIWYGProcessedEntityDTO
{
    /** @var EntityManager */
    private $entityManager;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

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
     * @param PropertyAccessorInterface $propertyAccessor
     * @param object $entity
     * @param array|null $changeSet
     */
    public function __construct(
        EntityManager $entityManager,
        PropertyAccessorInterface $propertyAccessor,
        $entity,
        ?array $changeSet = null
    ) {
        $this->entityManager = $entityManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->entity = $entity;
        $this->changeSet = $changeSet;
    }

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

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

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
        $value = null;
        if ($this->propertyAccessor->isReadable($this->getEntity(), $this->getFieldName())) {
            $value = $this->propertyAccessor->getValue($this->getEntity(), $this->getFieldName());
        }

        return $value;
    }

    public function isFieldChanged(): bool
    {
        if ($this->changeSet === null) {
            return true;
        }

        if (\array_key_exists($this->getFieldName(), $this->changeSet)) {
            return true;
        }

        if (\array_key_exists('serialized_data', $this->changeSet)
            && \array_key_exists($this->getFieldName(), $this->changeSet['serialized_data'][1])) {
            return true;
        }

        return false;
    }

    public function isRelation(): bool
    {
        return $this->getMetadata()->hasAssociation($this->getFieldName());
    }
}
