<?php

namespace Oro\Bundle\ShippingBundle\Model;

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

    /**
     * {@inheritDoc}
     */
    public function getWeight(): float
    {
        return $this->weight->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function getWeightUnitCode(): string
    {
        if (!$this->weight->getUnit()) {
            return '';
        }

        return $this->weight->getUnit()->getCode();
    }

    /**
     * {@inheritDoc}
     */
    public function getLength(): float
    {
        return $this->dimensions->getValue()->getLength();
    }

    /**
     * {@inheritDoc}
     */
    public function getWidth(): float
    {
        return $this->dimensions->getValue()->getWidth();
    }

    /**
     * {@inheritDoc}
     */
    public function getHeight(): float
    {
        return $this->dimensions->getValue()->getHeight();
    }

    /**
     * {@inheritDoc}
     */
    public function getDimensionsUnitCode(): string
    {
        if (!$this->dimensions->getUnit()) {
            return '';
        }

        return $this->dimensions->getUnit()->getCode();
    }

    /**
     * {@inheritDoc}
     */
    public function getGirth(): float
    {
        return $this->getLength() + 2 * $this->getWidth() + 2 * $this->getHeight();
    }
}
