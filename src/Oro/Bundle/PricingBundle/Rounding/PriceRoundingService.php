<?php

namespace Oro\Bundle\PricingBundle\Rounding;

use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;

use Oro\DBAL\Types\MoneyType;

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
