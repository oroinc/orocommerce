<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Settings\DataProvider;

use Oro\Bundle\PayPalBundle\Settings\DataProvider\BasicCardTypesDataProvider;

class BasicCardTypesDataProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCardTypes()
    {
        $provider = new BasicCardTypesDataProvider();

        $this->assertEquals([
            'visa',
            'mastercard',
            'discover',
            'american_express',
        ], $provider->getCardTypes());
    }

    public function testGetDefaultCardTypes()
    {
        $provider = new BasicCardTypesDataProvider();

        $this->assertEquals([
            'visa',
            'mastercard',
        ], $provider->getDefaultCardTypes());
    }
}
