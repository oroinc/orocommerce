<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\BasicPayPalCreditCardPaymentMethodFactory;
use Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\Routing\RouterInterface;

class BasicPayPalCreditCardPaymentMethodFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var BasicPayPalCreditCardPaymentMethodFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->factory = new BasicPayPalCreditCardPaymentMethodFactory($this->gateway, $this->router);
    }

    public function testCreate()
    {
        /** @var PayPalCreditCardConfigInterface $config */
        $config = $this->createMock(PayPalCreditCardConfigInterface::class);

        $method = new PayPalCreditCardPaymentMethod($this->gateway, $config, $this->router);

        $this->assertEquals($method, $this->factory->create($config));
    }
}
