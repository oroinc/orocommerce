<?php

namespace Oro\Bundle\ShippingBundle\Model;

/**
 * Container for shipping package physical characteristics.
 *
 * This class provides access to package dimensions and weight, implementing the
 * {@see ShippingPackageOptionsInterface} to expose individual measurements and calculated values
 * needed for shipping cost calculations.
 */
class ShippingPackageOptions implements ShippingPackageOptionsInterface
{
    /**
     * @var Dimensions
     */
    private $dimensions;

    /**
     * @var Weight
     */
    private $weight;

    public function __construct(Dimensions $dimensions, Weight $weight)
    {
        $this->dimensions = $dimensions;
        $this->weight = $weight;
    }

    #[\Override]
    public function getWeight(): float
    {
        return $this->weight->getValue();
    }

    #[\Override]
    public function getWeightUnitCode(): string
    {
        if (!$this->weight->getUnit()) {
            return '';
        }

        return $this->weight->getUnit()->getCode();
    }

    #[\Override]
    public function getLength(): float
    {
        return $this->dimensions->getValue()->getLength();
    }

    #[\Override]
    public function getWidth(): float
    {
        return $this->dimensions->getValue()->getWidth();
    }

    #[\Override]
    public function getHeight(): float
    {
        return $this->dimensions->getValue()->getHeight();
    }

    #[\Override]
    public function getDimensionsUnitCode(): string
    {
        if (!$this->dimensions->getUnit()) {
            return '';
        }

        return $this->dimensions->getUnit()->getCode();
    }

    #[\Override]
    public function getGirth(): float
    {
        return $this->getLength() + 2 * $this->getWidth() + 2 * $this->getHeight();
    }
}
