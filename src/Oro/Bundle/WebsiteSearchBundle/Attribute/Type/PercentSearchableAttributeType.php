<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

/**
 * Attribute type provides metadata for percent attribute for search index.
 */
class PercentSearchableAttributeType extends DecimalSearchableAttributeType
{
    /**
     * {@inheritdoc}
     */
    public function getFilterType(): string
    {
        return self::FILTER_TYPE_PERCENT;
    }
}
