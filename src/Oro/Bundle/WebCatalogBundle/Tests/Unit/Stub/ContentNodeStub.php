<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeStub extends ContentNode
{
    public function __construct(?int $id = null)
    {
        parent::__construct();

        if ($id !== null) {
            $this->id = $id;
        }
    }

    public function __clone()
    {
    }
}
