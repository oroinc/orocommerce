<?php

namespace OroB2B\Bundle\ProductBundle\Rounding;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Exception\InvalidRoundingTypeException;

class RoundingService
{
    const HALF_UP   = 'half_up';
    const HALF_DOWN = 'half_down';
    const CEIL      = 'ceil';
    const FLOOR     = 'floor';

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
     * Gets the an array of allowed types
     *
     * @return array
     */
    private function getAllowedTypes()
    {
        return [
            self::HALF_UP,
            self::HALF_DOWN,
            self::CEIL,
            self::FLOOR,
        ];
    }

    /**
     * @param float|integer $value
     * @param integer $precision
     * @return float|integer
     * @throws InvalidRoundingTypeException
     */
    public function round($value, $precision)
    {
        $roundType = $this->configManager->get('orob2b_product.unit_rounding_type');
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
                throw new InvalidRoundingTypeException(
                    sprintf(
                        'The type of the rounding is not valid. Allowed the following types: %s.',
                        implode(', ', $this->getAllowedTypes())
                    )
                );
                break;
        }

        return $value;
    }
}
