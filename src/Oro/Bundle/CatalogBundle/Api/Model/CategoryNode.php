<?php

namespace Oro\Bundle\CatalogBundle\Api\Model;

/**
 * The model for the master catalog tree node API resource.
 */
class CategoryNode
{
    /** @var int */
    private $id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
