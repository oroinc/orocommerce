<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Model;

use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use PHPUnit\Framework\TestCase;

class FedexPackageSettingsTest extends TestCase
{
    public function testGetters()
    {
        $weightUnit = 'kg';
        $dimensionsUnit = 'cm';
        $expression = '1 = 1';

        $settings = new FedexPackageSettings(
            $weightUnit,
            $dimensionsUnit,
            $expression,
            true
        );

        self::assertSame($weightUnit, $settings->getUnitOfWeight());
        self::assertSame($dimensionsUnit, $settings->getDimensionsUnit());
        self::assertSame($expression, $settings->getLimitationExpression());
        self::assertTrue($settings->isDimensionsIgnored());
    }
}
