<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\OneToManySearchableAttributeType;

class OneToManySearchableAttributeTypeTest extends ManyToManySearchableAttributeTypeTest
{
    #[\Override]
    protected function getSearchableAttributeTypeClassName()
    {
        return OneToManySearchableAttributeType::class;
    }
}
