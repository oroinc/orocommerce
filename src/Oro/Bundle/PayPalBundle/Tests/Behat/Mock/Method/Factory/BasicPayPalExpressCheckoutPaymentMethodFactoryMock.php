<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Mock\Method\Factory;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\BasicPayPalExpressCheckoutPaymentMethodFactory;
use Oro\Bundle\PayPalBundle\Method\Transaction\TransactionOptionProvider;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\Tests\Behat\Mock\Method\PayPalExpressCheckoutPaymentMethodMock;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;

/**
 * Factory to create instance of PayPal payment method mock.
 */
class BasicPayPalExpressCheckoutPaymentMethodFactoryMock extends BasicPayPalExpressCheckoutPaymentMethodFactory
{
    private Gateway $gateway;
    private RouterInterface $router;
    private PropertyAccessor $propertyAccessor;
    private TransactionOptionProvider $transactionOptionProvider;

    public function __construct(
        Gateway $gateway,
        RouterInterface $router,
        PropertyAccessor $propertyAccessor,
        TransactionOptionProvider $transactionOptionProvider
    ) {
        $this->gateway = $gateway;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor;
        $this->transactionOptionProvider = $transactionOptionProvider;
    }

    #[\Override]
    public function create(PayPalExpressCheckoutConfigInterface $config)
    {
        $method = new PayPalExpressCheckoutPaymentMethodMock(
            $this->gateway,
            $config,
            $this->propertyAccessor,
            $this->transactionOptionProvider->setConfig($config)
        );
        $method->setRouter($this->router);
        return $method;
    }
}
