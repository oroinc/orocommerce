<?php

namespace Oro\Bundle\ShippingBundle\Model;

/**
 * Defines the contract for accessing shipping package physical characteristics.
 *
 * This interface provides methods to retrieve package weight, dimensions (length, width, height),
 * their respective units of measurement, and calculated values like girth, which are essential
 * for shipping cost calculations and carrier API integrations.
 */
interface ShippingPackageOptionsInterface
{
    public function getWeight(): float;

    public function getWeightUnitCode(): string;

    public function getLength(): float;

    public function getWidth(): float;

    public function getHeight(): float;

    public function getDimensionsUnitCode(): string;

    public function getGirth(): float;
}
