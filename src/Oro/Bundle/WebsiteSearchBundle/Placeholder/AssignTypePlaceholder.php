<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * Provides the ASSIGN_TYPE placeholder for website search field name resolution.
 *
 * This placeholder is used in search field mappings to support dynamic assignment type-based indexing.
 * The default value is null, which means the placeholder is replaced with an empty string when no specific
 * assignment type is provided in the context. This allows for flexible field naming patterns
 * that can accommodate assignment type-specific data when needed.
 */
class AssignTypePlaceholder extends AbstractPlaceholder
{
    const NAME = 'ASSIGN_TYPE';

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
