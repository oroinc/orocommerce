<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderPaymentMethodFactory;
use Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderPaymentMethodFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;

class MoneyOrderPaymentMethodFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MoneyOrderPaymentMethodFactoryInterface
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = new MoneyOrderPaymentMethodFactory();
    }

    public function testCreate()
    {
        /** @var MoneyOrderConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(MoneyOrderConfigInterface::class);

        $method = new MoneyOrder($config);

        static::assertEquals($method, $this->factory->create($config));
    }
}
