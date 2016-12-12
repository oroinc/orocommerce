<?php

namespace Oro\Bundle\PricingBundle\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;

class UnitPlaceholder extends AbstractPlaceholder
{
    const NAME = 'UNIT';

    /**
     * {@inheritdoc}
     */
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return '';
    }
}
