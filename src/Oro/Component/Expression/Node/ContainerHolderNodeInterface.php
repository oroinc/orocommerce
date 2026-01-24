<?php

namespace Oro\Component\Expression\Node;

/**
 * Defines the contract for expression nodes that reference containers (entities) with optional identifiers.
 *
 * Containers represent entity classes or aliases in expressions. This interface provides methods
 * to access the container name, its resolved form (including optional container ID), and the
 * container ID itself, which is useful for distinguishing between multiple instances of the same entity.
 */
interface ContainerHolderNodeInterface
{
    /**
     * @return string
     */
    public function getContainer();

    /**
     * @return string
     */
    public function getResolvedContainer();

    /**
     * @return int|null|string
     */
    public function getContainerId();
}
