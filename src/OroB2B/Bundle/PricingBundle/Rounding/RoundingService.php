<?php

namespace OroB2B\Bundle\PricingBundle\Rounding;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class RoundingService
{
    const HALF_UP = 'half_up';
    const HALF_DOWN = 'half_down';
    const CEIL = 'ceil';
    const FLOOR = 'floor';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param float|integer $value
     * @param integer $precision
     * @param string $roundType
     * @return float|int
     */
    public function round($value, $precision = null, $roundType = null)
    {
        if (null === $roundType) {
            $roundType = $this->configManager->get('orob2b_pricing.rounding_type', false, self::HALF_UP);
        }

        if (null === $precision) {
            $precision = $this->configManager->get('orob2b_pricing.default_precision', false, 4);
        }

        $multiplier = pow(10, $precision);

        switch ($roundType) {
            case self::HALF_UP:
                $value = round($value, $precision, PHP_ROUND_HALF_UP);
                break;
            case self::HALF_DOWN:
                $value = round($value, $precision, PHP_ROUND_HALF_DOWN);
                break;
            case self::CEIL:
                $value = ceil($value * $multiplier) / $multiplier;
                break;
            case self::FLOOR:
                $value = floor($value * $multiplier) / $multiplier;
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'The type of the rounding is not valid. Allowed the following types: %s.',
                        implode(', ', [self::HALF_UP, self::HALF_DOWN, self::CEIL, self::FLOOR])
                    )
                );
                break;
        }

        return $value;
    }
}
