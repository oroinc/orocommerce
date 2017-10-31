<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\ManyToManySearchableAttributeType;

class ManyToManySearchableAttributeTypeTest extends ManyToOneSearchableAttributeTypeTest
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return ManyToManySearchableAttributeType::class;
    }
}
