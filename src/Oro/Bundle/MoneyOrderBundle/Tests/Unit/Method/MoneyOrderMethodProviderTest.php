<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method;

use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrderMethodProvider;

class MoneyOrderMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MoneyOrderMethodProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new MoneyOrderMethodProvider();
    }

    public function testGetPaymentMethods()
    {
        static::assertEquals([MoneyOrder::TYPE => new MoneyOrder()], $this->provider->getPaymentMethods());
    }

    public function testGetPaymentMethod()
    {
        static::assertEquals(new MoneyOrder(), $this->provider->getPaymentMethod(MoneyOrder::TYPE));
    }

    public function testHasPaymentMethod()
    {
        static::assertTrue($this->provider->hasPaymentMethod(MoneyOrder::TYPE));
        static::assertFalse($this->provider->hasPaymentMethod('not_existing'));
    }

    public function testGetType()
    {
        static::assertEquals(MoneyOrder::TYPE, $this->provider->getType());
    }
}
