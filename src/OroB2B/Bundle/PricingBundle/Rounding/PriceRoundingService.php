<?php

namespace OroB2B\Bundle\PricingBundle\Rounding;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\DBAL\Types\MoneyType;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\ProductBundle\Rounding\AbstractRoundingService;

class PriceRoundingService extends AbstractRoundingService
{
    const FALLBACK_PRECISION = MoneyType::TYPE_SCALE;

    /** {@inheritdoc} */
    public function getRoundType()
    {
        $key = OroB2BPricingExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . Configuration::ROUNDING_TYPE;
        return (int)$this->configManager->get($key, self::ROUND_HALF_UP);
    }

    /** {@inheritdoc} */
    public function getPrecision()
    {
        $key = OroB2BPricingExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . Configuration::PRECISION;
        return (int)$this->configManager->get($key, self::FALLBACK_PRECISION);
    }
}
