<?php

namespace Oro\Bundle\CatalogBundle\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;

class CategoryPathPlaceholder extends AbstractPlaceholder
{
    public const NAME = 'CATEGORY_PATH';

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
