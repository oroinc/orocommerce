<?php

namespace Oro\Bundle\ShippingBundle\Model;

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
