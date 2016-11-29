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
     * todo: fix in BB-4178
     */
    public function getDefaultValue()
    {
        return '';
    }
}
