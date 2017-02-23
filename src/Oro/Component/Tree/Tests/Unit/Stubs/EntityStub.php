<?php

namespace Oro\Component\Tree\Tests\Unit\Stubs;

class EntityStub
{
    /**
     * @var int|string
     */
    public $id;

    /**
     * @var string
     */
    public $text;

    /**
     * @var int|string
     */
    public $parent;

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
}
