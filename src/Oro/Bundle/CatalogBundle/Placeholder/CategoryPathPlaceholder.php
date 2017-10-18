<?php

namespace Oro\Bundle\CatalogBundle\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;

class CategoryPathPlaceholder extends AbstractPlaceholder
{
    const NAME = 'CATEGORY_PATH';

    /**
     * {@inheritDoc}
     */
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultValue()
    {
        return null;
    }
}
