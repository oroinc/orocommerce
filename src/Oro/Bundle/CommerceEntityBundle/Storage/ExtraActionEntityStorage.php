<?php

namespace Oro\Bundle\CommerceEntityBundle\Storage;

use Doctrine\Common\Util\ClassUtils as DoctrineClassUtils;

/**
 * Stores entities scheduled for extra insert operations.
 *
 * Manages a collection of entities that need to be inserted as part of extra actions,
 * allowing deferred insertion of related entities during transaction processing.
 */
class ExtraActionEntityStorage implements ExtraActionEntityStorageInterface
{
    /**
     * @var object[]
     */
    protected $entities = [];

    #[\Override]
    public function scheduleForExtraInsert($entity)
    {
        if (! is_object($entity)) {
            throw new \InvalidArgumentException(sprintf('Expected type is object, %s given', gettype($entity)));
        }

        $this->entities[DoctrineClassUtils::getClass($entity)][] = $entity;
    }

    #[\Override]
    public function clearScheduledForInsert()
    {
        $this->entities = [];
    }

    #[\Override]
    public function getScheduledForInsert($className = null)
    {
        if ($className) {
            return array_key_exists($className, $this->entities) ? $this->entities[$className] : [];
        }

        return $this->entities;
    }
}
