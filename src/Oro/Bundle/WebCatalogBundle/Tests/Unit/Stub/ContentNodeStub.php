<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class ContentNodeStub extends ContentNode
{
    public function __construct(?int $id = null, ?WebCatalog $webCatalog = null)
    {
        parent::__construct();

        if ($id !== null) {
            $this->id = $id;
        }

        if ($webCatalog !== null) {
            $this->webCatalog = $webCatalog;
        }
    }

    #[\Override]
    public function __clone()
    {
    }
}
