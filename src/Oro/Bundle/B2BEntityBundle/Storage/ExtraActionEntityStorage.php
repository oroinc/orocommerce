<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

class ExtraActionEntityStorage implements ExtraActionEntityStorageInterface
{
    /**
     * @var array
     */
    protected $entities = [];

    /**
     * {@inheritdoc}
     */
    public function scheduleForExtraInsert($entity)
    {
        $this->entities[] = $entity;
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
        foreach ($this->entities as $entityInStorage) {
            if ($entity == $entityInStorage) {
                return true;
            }
        }

        return false;
    }
}
