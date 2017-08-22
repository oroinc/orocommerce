<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Model;

use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use PHPUnit\Framework\TestCase;

class FedexPackageSettingsTest extends TestCase
{
    const WEIGHT = 4.9;
    const LENGTH = 2.6;
    const GIRTH = 4.8;
    const WEIGHT_UNIT = 'kg';
    const DIMENSIONS_UNIT = 'cm';

    public function testGetters()
    {
        $settings = new FedexPackageSettings(
            self::WEIGHT,
            self::LENGTH,
            self::GIRTH,
            self::WEIGHT_UNIT,
            self::DIMENSIONS_UNIT
        );

        static::assertSame(self::WEIGHT, $settings->getMaxWeight());
        static::assertSame(self::LENGTH, $settings->getMaxLength());
        static::assertSame(self::GIRTH, $settings->getMaxGirth());
        static::assertSame(self::WEIGHT_UNIT, $settings->getUnitOfWeight());
        static::assertSame(self::DIMENSIONS_UNIT, $settings->getDimensionsUnit());
    }
}
