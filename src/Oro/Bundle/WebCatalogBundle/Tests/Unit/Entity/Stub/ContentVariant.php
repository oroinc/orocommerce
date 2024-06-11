<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant as BaseContentVariant;

class ContentVariant extends BaseContentVariant
{
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }
}
