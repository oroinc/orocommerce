<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View\Factory;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\Factory\BasicPayPalExpressCheckoutPaymentMethodViewFactory;
use Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView;

class BasicPayPalExpressCheckoutPaymentMethodViewFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BasicPayPalExpressCheckoutPaymentMethodViewFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new BasicPayPalExpressCheckoutPaymentMethodViewFactory();
    }

    public function testCreate()
    {
        /** @var PayPalExpressCheckoutConfigInterface $config */
        $config = $this->createMock(PayPalExpressCheckoutConfigInterface::class);

        $expectedView = new PayPalExpressCheckoutPaymentMethodView($config);

        $this->assertEquals($expectedView, $this->factory->create($config));
    }
}
