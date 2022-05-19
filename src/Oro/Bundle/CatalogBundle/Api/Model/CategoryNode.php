<?php

namespace Oro\Bundle\CatalogBundle\Api\Model;

/**
 * Represents the master catalog tree node.
 */
class CategoryNode
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
