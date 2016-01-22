<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

class ExtraActionEntityStorage implements ExtraActionEntityStorageInterface
{
    /**
     * @var ObjectIdentifierAwareInterface[]
     */
    protected $entities = [];

    /**
     * {@inheritdoc}
     */
    public function scheduleForExtraInsert(ObjectIdentifierAwareInterface $entity)
    {
        $this->entities[$entity->getObjectIdentifier()] = $entity;
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
    public function isScheduledForInsert(ObjectIdentifierAwareInterface $entity)
    {
        return array_key_exists($entity->getObjectIdentifier(), $this->entities);
    }
}
