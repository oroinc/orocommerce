<?php

namespace Oro\Bundle\CurrencyBundle\Rounding;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\DBAL\Types\MoneyType;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;

class PriceRoundingService extends AbstractRoundingService
{
    const FALLBACK_PRECISION = MoneyType::TYPE_SCALE;

    /** {@inheritdoc} */
    public function getRoundType()
    {
        $key = OroPricingExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . Configuration::ROUNDING_TYPE;
        return (int)$this->configManager->get($key, self::ROUND_HALF_UP);
    }

    /** {@inheritdoc} */
    public function getPrecision()
    {
        $key = OroPricingExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . Configuration::PRECISION;
        return (int)$this->configManager->get($key, self::FALLBACK_PRECISION);
    }
}
