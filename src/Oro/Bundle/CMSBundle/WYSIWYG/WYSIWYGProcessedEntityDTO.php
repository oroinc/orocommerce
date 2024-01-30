<?php

namespace Oro\Bundle\CMSBundle\WYSIWYG;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * DTO model for entity with wysiwyg fields
 */
class WYSIWYGProcessedEntityDTO
{
    private EntityManagerInterface $entityManager;
    private PropertyAccessorInterface $propertyAccessor;
    private object $entity;
    private ?string $fieldName = null;
    private ?string $fieldType = null;
    private ?ClassMetadata $metadata = null;
    private ?array $changeSet;

    public function __construct(
        EntityManagerInterface $entityManager,
        PropertyAccessorInterface $propertyAccessor,
        object $entity,
        ?array $changeSet = null
    ) {
        $this->entityManager = $entityManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->entity = $entity;
        $this->changeSet = $changeSet;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getEntity(): object
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
     * @return $this
     */
    public function withField(string $fieldName, ?string $fieldType = null): WYSIWYGProcessedEntityDTO
    {
        $dto = clone $this;
        $dto->fieldName = $fieldName;
        $dto->fieldType = $fieldType;

        return $dto;
    }

    public function getFieldValue(): mixed
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
