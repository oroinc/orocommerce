<?php

namespace OroB2B\Bundle\PricingBundle\Rounding;

use Oro\DBAL\Types\MoneyType;

use OroB2B\Bundle\ProductBundle\Rounding\AbstractRoundingService;

class PriceRoundingService extends AbstractRoundingService
{
    const FALLBACK_PRECISION = MoneyType::TYPE_SCALE;

    /** {@inheritdoc} */
    public function getRoundType()
    {
        return $this->configManager->get('oro_b2b_pricing.rounding_type', self::ROUND_HALF_UP);
    }

    /** {@inheritdoc} */
    public function getPrecision()
    {
        return self::FALLBACK_PRECISION;
    }
}
