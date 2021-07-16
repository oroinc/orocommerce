<?php

namespace Oro\Bundle\PayPalBundle\Method\Factory;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\Routing\RouterInterface;

class BasicPayPalCreditCardPaymentMethodFactory implements PayPalCreditCardPaymentMethodFactoryInterface
{
    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(Gateway $gateway, RouterInterface $router)
    {
        $this->gateway = $gateway;
        $this->router = $router;
    }

    /**
     * @param PayPalCreditCardConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(PayPalCreditCardConfigInterface $config)
    {
        return new PayPalCreditCardPaymentMethod(
            $this->gateway,
            $config,
            $this->router
        );
    }
}
