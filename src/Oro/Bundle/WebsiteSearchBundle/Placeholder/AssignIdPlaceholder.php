<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * Provides the ASSIGN_ID placeholder for website search field name resolution.
 *
 * This placeholder is used in search field mappings to support dynamic assignment-based indexing.
 * The default value is null, which means the placeholder is replaced with an empty string when no specific
 * assignment ID is provided in the context. This allows for flexible field naming patterns
 * that can accommodate assignment-specific data when needed.
 */
class AssignIdPlaceholder extends AbstractPlaceholder
{
    const NAME = 'ASSIGN_ID';

    #[\Override]
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * @return null
     */
    #[\Override]
    public function getDefaultValue()
    {
        return null;
    }
}
