<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

class PercentSearchableAttributeType extends DecimalSearchableAttributeType
{
    /**
     * {@inheritdoc}
     */
    public function getFilterType()
    {
        return self::FILTER_TYPE_PERCENT;
    }
}
