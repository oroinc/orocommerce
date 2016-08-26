<?php

namespace Oro\Bundle\B2BEntityBundle\Storage;

use Doctrine\Common\Util\ClassUtils as DoctrineClassUtils;

class ExtraActionEntityStorage implements ExtraActionEntityStorageInterface
{
    /**
     * @var object[]
     */
    protected $entities = [];

    /**
     * {@inheritdoc}
     */
    public function scheduleForExtraInsert($entity)
    {
        if (! is_object($entity)) {
            throw new \InvalidArgumentException(sprintf('Expected type is object, %s given', gettype($entity)));
        }

        $this->entities[DoctrineClassUtils::getClass($entity)][] = $entity;
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
    public function getScheduledForInsert($className = null)
    {
        if ($className) {
            return array_key_exists($className, $this->entities) ? $this->entities[$className] : [];
        }

        return $this->entities;
    }
}
