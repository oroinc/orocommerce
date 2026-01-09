<?php

namespace Oro\Bundle\OrderBundle\Doctrine\ORM\Id;

/**
 * Generates identifiers by extracting the entity's ID directly.
 *
 * A simple implementation of {@see EntityAwareGeneratorInterface} that retrieves the identifier from an entity
 * by calling its `getId()` method.
 * This is useful for entities that already have a primary key that can serve as the identifier.
 */
class SimpleEntityAwareGenerator implements EntityAwareGeneratorInterface
{
    /**
     * @param object $entity
     *
     * @return string
     */
    #[\Override]
    public function generate($entity)
    {
        return $entity->getId();
    }
}
