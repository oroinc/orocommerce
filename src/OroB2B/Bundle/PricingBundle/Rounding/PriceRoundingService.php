<?php

namespace OroB2B\Bundle\PricingBundle\Rounding;

use Oro\DBAL\Types\MoneyType;

use OroB2B\Bundle\ProductBundle\Rounding\AbstractRoundingService;

class PriceRoundingService extends AbstractRoundingService
{
    const FALLBACK_PRECISION = MoneyType::TYPE_SCALE;

    /** {@inheritdoc} */
    protected function getRoundType()
    {
        return $this->configManager->get('orob2b_pricing.rounding_type', self::HALF_UP);
    }

    /** {@inheritdoc} */
    protected function getFallbackPrecision()
    {
        return self::FALLBACK_PRECISION;
    }
}
