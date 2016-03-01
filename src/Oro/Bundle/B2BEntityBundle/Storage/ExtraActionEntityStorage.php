<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

class ExtraActionEntityStorage implements ExtraActionEntityStorageInterface
{
    /**
     * @var ObjectIdentifierAwareInterface|object[]
     */
    protected $entities = [];

    /**
     * {@inheritdoc}
     */
    public function scheduleForExtraInsert($entity)
    {
        $this->entities[$this->getObjectIdentifier($entity)] = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function hasScheduledForInsert()
    {
        return 0 !== count($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function clearScheduledForInsert()
    {
        $this->entities = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledForInsert()
    {
        return $this->entities;
    }

    /**
     * {@inheritdoc}
     */
    public function isScheduledForInsert($entity)
    {
        return array_key_exists($this->getObjectIdentifier($entity), $this->entities);
    }

    /**
     * @param ObjectIdentifierAwareInterface|object $entity
     * @return string
     */
    protected function getObjectIdentifier($entity)
    {
        if (!is_object($entity)) {
            throw new \InvalidArgumentException(sprintf('Expected type is object, %s given', gettype($entity)));
        }

        if ($entity instanceof ObjectIdentifierAwareInterface) {
            return $entity->getObjectIdentifier();
        }

        return spl_object_hash($entity);
    }
}
