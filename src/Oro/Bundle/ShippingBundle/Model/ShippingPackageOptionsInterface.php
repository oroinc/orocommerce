<?php

namespace Oro\Bundle\ShippingBundle\Model;

interface ShippingPackageOptionsInterface
{
    /**
     * @return float
     */
    public function getWeight(): float;

    /**
     * @return string
     */
    public function getWeightUnitCode(): string;

    /**
     * @return float
     */
    public function getLength(): float;

    /**
     * @return float
     */
    public function getWidth(): float;

    /**
     * @return float
     */
    public function getHeight(): float;

    /**
     * @return string
     */
    public function getDimensionsUnitCode(): string;

    /**
     * @return float
     */
    public function getGirth(): float;
}
