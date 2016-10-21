<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class MeasureUnitConversion
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $lengthConfigEntryName;

    /** @var string */
    protected $weightConfigEntryName;

    /**
     * @param ConfigManager $configManager
     * @param string $lengthConfigEntryName
     * @param string $weightConfigEntryName
     */
    public function __construct(ConfigManager $configManager, $lengthConfigEntryName, $weightConfigEntryName)
    {
        $this->configManager = $configManager;
        $this->lengthConfigEntryName = $lengthConfigEntryName;
        $this->weightConfigEntryName = $weightConfigEntryName;
    }

    /**
     * @param Dimensions|Weight $unit
     * @param string $shippingUnitCode
     * @return null|Dimensions|Weight
     * @throws \InvalidArgumentException
     */
    public function convert($unit, $shippingUnitCode)
    {
        switch (get_class($unit)) {
            case Dimensions::class:
                return $this->convertDimensions($unit, $shippingUnitCode);
            case Weight::class:
                return $this->convertWeight($unit, $shippingUnitCode);
            default:
                throw new \InvalidArgumentException('Invalid argument passed to convert function.');
        }
    }

    /**
     * @param Dimensions $unit
     * @param string $shippingUnitCode
     * @return null|Dimensions
     */
    public function convertDimensions(Dimensions $unit, $shippingUnitCode)
    {
        if ($this->isDimensionsEnabled($unit)) {
            $conversionRates = $unit->getUnit()->getConversionRates();

            if ($shippingUnitCode === $unit->getUnit()->getCode()) {
                return $unit;
            }

            if (array_key_exists($shippingUnitCode, $conversionRates)) {
                return Dimensions::create(
                    $unit->getValue()->getLength() * $conversionRates[$shippingUnitCode],
                    $unit->getValue()->getWidth() * $conversionRates[$shippingUnitCode],
                    $unit->getValue()->getHeight() * $conversionRates[$shippingUnitCode],
                    (new LengthUnit)->setCode($shippingUnitCode)
                );
            }
        }

        return null;
    }

    /**
     * @param Weight $unit
     * @param string $shippingUnitCode
     * @return null|Weight
     */
    public function convertWeight(Weight $unit, $shippingUnitCode)
    {
        if ($this->isWeightEnabled($unit)) {
            $conversionRates = $unit->getUnit()->getConversionRates();

            if ($shippingUnitCode === $unit->getUnit()->getCode()) {
                return $unit;
            }

            if (array_key_exists($shippingUnitCode, $conversionRates)) {
                return Weight::create(
                    $unit->getValue() * $conversionRates[$shippingUnitCode],
                    (new WeightUnit)->setCode($shippingUnitCode)
                );
            }
        }

        return null;
    }

    /**
     * @param Dimensions $unit
     * @return bool
     */
    public function isDimensionsEnabled(Dimensions $unit)
    {
        return in_array($unit->getUnit()->getCode(), $this->configManager->get($this->lengthConfigEntryName), null);
    }

    /**
     * @param Weight $unit
     * @return bool
     */
    public function isWeightEnabled(Weight $unit)
    {
        return in_array($unit->getUnit()->getCode(), $this->configManager->get($this->weightConfigEntryName), null);
    }
}
