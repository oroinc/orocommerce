<?php

namespace Oro\Bundle\PricingBundle\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;

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
