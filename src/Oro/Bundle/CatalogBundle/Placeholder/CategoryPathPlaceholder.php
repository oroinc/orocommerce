<?php

namespace Oro\Bundle\CatalogBundle\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;

/**
 * Placeholder for category path in website search.
 *
 * Provides a placeholder that can be used in website search templates to represent
 * the materialized path of a category in the category hierarchy.
 */
class CategoryPathPlaceholder extends AbstractPlaceholder
{
    const NAME = 'CATEGORY_PATH';

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
