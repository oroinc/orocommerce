<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Model;

use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use PHPUnit\Framework\TestCase;

class FedexPackageSettingsTest extends TestCase
{
    const WEIGHT_UNIT = 'kg';
    const DIMENSIONS_UNIT = 'cm';
    const EXPRESSION = '1 = 1';

    public function testGetters()
    {
        $settings = new FedexPackageSettings(
            self::WEIGHT_UNIT,
            self::DIMENSIONS_UNIT,
            self::EXPRESSION,
            true
        );

        static::assertSame(self::WEIGHT_UNIT, $settings->getUnitOfWeight());
        static::assertSame(self::DIMENSIONS_UNIT, $settings->getDimensionsUnit());
        static::assertSame(self::EXPRESSION, $settings->getLimitationExpression());
        static::assertTrue($settings->isDimensionsIgnored());
    }
}
