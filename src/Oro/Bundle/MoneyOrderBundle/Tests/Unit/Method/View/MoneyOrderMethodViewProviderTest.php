<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\View;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderMethodViewProvider;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;

class MoneyOrderMethodViewProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MoneyOrderConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var MoneyOrderMethodViewProvider
     */
    protected $viewProvider;

    protected function setUp()
    {
        $this->config = $this->createMock(MoneyOrderConfigInterface::class);
        $this->viewProvider = new MoneyOrderMethodViewProvider($this->config);
    }

    public function testGetPaymentMethodViews()
    {
        $paymentMethods = [MoneyOrder::TYPE];
        static::assertEquals(
            [MoneyOrder::TYPE => new MoneyOrderView($this->config)],
            $this->viewProvider->getPaymentMethodViews($paymentMethods)
        );
    }

    public function testGetPaymentMethodView()
    {
        static::assertEquals(
            new MoneyOrderView($this->config),
            $this->viewProvider->getPaymentMethodView(MoneyOrder::TYPE)
        );
    }

    public function testHasPaymentMethodView()
    {
        static::assertTrue($this->viewProvider->hasPaymentMethodView(MoneyOrder::TYPE));
        static::assertFalse($this->viewProvider->hasPaymentMethodView('notExisting'));
    }
}
