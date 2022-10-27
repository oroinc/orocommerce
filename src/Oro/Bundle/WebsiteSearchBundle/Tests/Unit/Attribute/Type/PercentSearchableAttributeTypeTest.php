<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\PercentSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;

class PercentSearchableAttributeTypeTest extends DecimalSearchableAttributeTypeTest
{
    /**
     * {@inheritDoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return PercentSearchableAttributeType::class;
    }

    public function testGetFilterType()
    {
        $this->assertSame(
            SearchAttributeTypeInterface::FILTER_TYPE_PERCENT,
            $this->getSearchableAttributeType()->getFilterType($this->attribute)
        );
    }
}
