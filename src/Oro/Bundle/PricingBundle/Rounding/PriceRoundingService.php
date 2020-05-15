<?php

namespace Oro\Bundle\PricingBundle\Rounding;

use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\DBAL\Types\MoneyType;

/**
 * Rounds price value according to system configuration settings.
 */
class PriceRoundingService extends AbstractRoundingService
{
    /** @var int */
    const FALLBACK_PRECISION = MoneyType::TYPE_SCALE;

    /** @var int|null */
    private $roundType;

    /** @var int|null */
    private $precision;

    /** {@inheritdoc} */
    public function getRoundType()
    {
        if ($this->roundType === null) {
            $this->roundType = (int) ($this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::ROUNDING_TYPE)
            ) ?? self::ROUND_HALF_UP);
        }

        return $this->roundType;
    }

    /** {@inheritdoc} */
    public function getPrecision()
    {
        if ($this->precision === null) {
            $this->precision = (int) ($this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::PRECISION)
            ) ?? self::FALLBACK_PRECISION);
        }

        return $this->precision;
    }
}
