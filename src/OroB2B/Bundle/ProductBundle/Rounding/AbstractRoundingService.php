<?php

namespace OroB2B\Bundle\ProductBundle\Rounding;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Exception\InvalidRoundingTypeException;

abstract class AbstractRoundingService implements RoundingServiceInterface
{
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
     * @param int $precision
     * @param string $roundType
     * @return float|int
     * @throws InvalidRoundingTypeException
     */
    public function round($value, $precision = null, $roundType = null)
    {
        if (null === $roundType) {
            $roundType = (string)$this->getRoundType();
        }

        if (null === $precision) {
            $precision = (int)$this->getFallbackPrecision();
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
                throw new InvalidRoundingTypeException(
                    sprintf(
                        'The type of the rounding is not valid. Allowed the following types: %s.',
                        implode(', ', [self::HALF_UP, self::HALF_DOWN, self::CEIL, self::FLOOR])
                    )
                );
                break;
        }

        return $value;
    }

    /**
     * @return string
     */
    abstract protected function getRoundType();

    /**
     * @return int
     */
    abstract protected function getFallbackPrecision();
}
