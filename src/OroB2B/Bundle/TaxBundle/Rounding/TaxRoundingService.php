<?php

namespace OroB2B\Bundle\TaxBundle\Rounding;

use OroB2B\Bundle\ProductBundle\Rounding\AbstractRoundingService;

class TaxRoundingService extends AbstractRoundingService
{
    const TAX_PRECISION = 2;

    /** {@inheritdoc} */
    protected function getRoundType()
    {
        return self::HALF_UP;
    }

    /** {@inheritdoc} */
    protected function getFallbackPrecision()
    {
        return self::TAX_PRECISION;
    }
}
