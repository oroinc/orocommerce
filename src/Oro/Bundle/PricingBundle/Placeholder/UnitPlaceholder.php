<?php

namespace Oro\Bundle\PricingBundle\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;

/**
 * Placeholder for product units in website search queries.
 *
 * Provides the UNIT placeholder value for website search indexing, supporting unit-aware search and filtering.
 */
class UnitPlaceholder extends AbstractPlaceholder
{
    const NAME = 'UNIT';

    #[\Override]
    public function getPlaceholder()
    {
        return self::NAME;
    }

    #[\Override]
    public function getDefaultValue()
    {
        return '';
    }
}
