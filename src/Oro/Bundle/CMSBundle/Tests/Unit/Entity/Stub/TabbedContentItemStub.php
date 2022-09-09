<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\CMSBundle\Entity\TabbedContentItem;

class TabbedContentItemStub extends TabbedContentItem
{
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }
}
