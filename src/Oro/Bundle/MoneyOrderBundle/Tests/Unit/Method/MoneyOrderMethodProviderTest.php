<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProvider;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrderMethodProvider;

class MoneyOrderMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MoneyOrderMethodProvider
     */
    private $provider;

    /**
     * @var MoneyOrderConfig[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    private $configs;

    protected function setUp()
    {
        $this->configs = [
            $this->createMock(MoneyOrderConfig::class),
            $this->createMock(MoneyOrderConfig::class),
        ];

        $configProvider = $this->createMock(MoneyOrderConfigProvider::class);
        $configProvider->expects(static::any())
            ->method('getPaymentConfigs')
            ->willReturn($this->configs);

        $this->provider = new MoneyOrderMethodProvider($configProvider);
    }

    public function testGetPaymentMethodsReturnsCorrectObjects()
    {
        $method1 = new MoneyOrder($this->configs[0]);
        $method2 = new MoneyOrder($this->configs[1]);
        $expected = [
            $method1->getIdentifier() => $method1,
            $method2->getIdentifier() => $method2,
        ];

        static::assertEquals($expected, $this->provider->getPaymentMethods());
    }

    public function testGetPaymentMethodReturnsCorrectObject()
    {
        $method = new MoneyOrder($this->configs[0]);

        static::assertEquals(
            $method,
            $this->provider->getPaymentMethod($method->getIdentifier())
        );
    }

    public function testGetPaymentMethodForWrongIdentifier()
    {
        static::assertNull($this->provider->getPaymentMethod('wrong'));
    }

    public function testHasPaymentMethodForCorrectIdentifier()
    {
        $method = new MoneyOrder($this->configs[0]);

        static::assertTrue($this->provider->hasPaymentMethod($method->getIdentifier()));
    }

    public function testHasPaymentMethodForWrongIdentifier()
    {
        static::assertFalse($this->provider->hasPaymentMethod('wrong'));
    }

    public function testGetType()
    {
        static::assertEquals('money_order', $this->provider->getType());
    }
}
