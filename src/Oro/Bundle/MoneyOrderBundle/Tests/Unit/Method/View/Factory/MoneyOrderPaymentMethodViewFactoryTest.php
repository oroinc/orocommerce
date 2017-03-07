<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\View\Factory;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactory;
use Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;

class MoneyOrderPaymentMethodViewFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MoneyOrderPaymentMethodViewFactoryInterface
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = new MoneyOrderPaymentMethodViewFactory();
    }

    public function testCreate()
    {
        /** @var MoneyOrderConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(MoneyOrderConfigInterface::class);

        $method = new MoneyOrderView($config);

        static::assertEquals($method, $this->factory->create($config));
    }
}
