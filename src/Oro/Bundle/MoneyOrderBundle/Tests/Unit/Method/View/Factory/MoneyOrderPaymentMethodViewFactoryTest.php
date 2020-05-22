<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\View\Factory;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactory;
use Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;

class MoneyOrderPaymentMethodViewFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MoneyOrderPaymentMethodViewFactoryInterface
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new MoneyOrderPaymentMethodViewFactory();
    }

    public function testCreate()
    {
        /** @var MoneyOrderConfigInterface|\PHPUnit\Framework\MockObject\MockObject $config */
        $config = $this->createMock(MoneyOrderConfigInterface::class);

        $method = new MoneyOrderView($config);

        static::assertEquals($method, $this->factory->create($config));
    }
}
