<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\PricingBundle\Provider\ChainCurrentCurrencyProvider;
use Oro\Bundle\PricingBundle\Provider\CurrentCurrencyProviderInterface;

class ChainCurrentCurrencyProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCurrentCurrencyAndNoProviderss()
    {
        $chainProvider = new ChainCurrentCurrencyProvider([]);

        $this->assertNull($chainProvider->getCurrentCurrency());
    }

    public function testGetCurrentCurrency()
    {
        $currency = 'USD';

        $provider1 = $this->createMock(CurrentCurrencyProviderInterface::class);
        $provider2 = $this->createMock(CurrentCurrencyProviderInterface::class);
        $provider3 = $this->createMock(CurrentCurrencyProviderInterface::class);

        $provider1->expects(self::once())
            ->method('getCurrentCurrency')
            ->willReturn(null);
        $provider2->expects(self::once())
            ->method('getCurrentCurrency')
            ->willReturn($currency);
        $provider3->expects(self::never())
            ->method('getCurrentCurrency');

        $chainProvider = new ChainCurrentCurrencyProvider([
            $provider1,
            $provider2,
            $provider3
        ]);

        $this->assertEquals($currency, $chainProvider->getCurrentCurrency());
        // test that the result is cached
        $this->assertEquals($currency, $chainProvider->getCurrentCurrency());
    }

    public function testGetCurrentCurrencyWhenAllProvidersDidNotReturnLocalization()
    {
        $provider1 = $this->createMock(CurrentCurrencyProviderInterface::class);

        $provider1->expects(self::once())
            ->method('getCurrentCurrency')
            ->willReturn(null);

        $chainProvider = new ChainCurrentCurrencyProvider([
            $provider1
        ]);

        $this->assertNull($chainProvider->getCurrentCurrency());
        // test that the result is cached
        $this->assertNull($chainProvider->getCurrentCurrency());
    }
}
