<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * Provides the `ENUM_ID` placeholder for website search field name resolution.
 *
 * This placeholder is used in search field mappings to support dynamic enum-based indexing.
 * The default value is null, which means the placeholder is replaced with an empty string when no specific enum ID
 * is provided in the context. This allows for flexible field naming patterns that can accommodate enum-specific data
 * when needed, particularly useful for indexing enum attribute values with their corresponding IDs.
 */
class EnumIdPlaceholder extends AbstractPlaceholder
{
    public const NAME = 'ENUM_ID';

    #[\Override]
    public function getPlaceholder()
    {
        return self::NAME;
    }

    #[\Override]
    public function getDefaultValue()
    {
        return null;
    }
}
