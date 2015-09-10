<?php

namespace Oro\Component\Testing\Unit\Entity\Stub;

class StubEntity
{
    /** @var int */
    protected $id;

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
