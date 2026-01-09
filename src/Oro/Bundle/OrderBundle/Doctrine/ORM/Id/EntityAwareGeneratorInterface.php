<?php

namespace Oro\Bundle\OrderBundle\Doctrine\ORM\Id;

/**
 * Defines the contract for generating identifiers based on entity instances.
 *
 * Implementations of this interface are responsible for generating unique identifiers for entities,
 * allowing for custom ID generation strategies that may depend on entity properties or state.
 */
interface EntityAwareGeneratorInterface
{
    /**
     * @param object $entity
     *
     * @return string
     */
    public function generate($entity);
}
