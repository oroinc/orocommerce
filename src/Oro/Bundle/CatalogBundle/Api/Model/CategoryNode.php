<?php

namespace Oro\Bundle\CatalogBundle\Api\Model;

/**
 * The model for the master catalog tree node API resource.
 */
class CategoryNode
{
    /** @var int */
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
