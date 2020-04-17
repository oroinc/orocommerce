<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\EnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\FulltextAwareTypeInterface;

class EnumSearchableAttributeTypeStub extends EnumSearchableAttributeType implements
    FulltextAwareTypeInterface
{
    public function isFulltextSearchSupported(): bool
    {
        return false;
    }
}
