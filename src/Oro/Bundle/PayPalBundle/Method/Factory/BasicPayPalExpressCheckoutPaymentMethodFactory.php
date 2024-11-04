<?php

namespace Oro\Bundle\PayPalBundle\Method\Factory;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\PayPalBundle\Method\Transaction\TransactionOptionProvider;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Factory to create instance of PayPal payment method.
 */
class BasicPayPalExpressCheckoutPaymentMethodFactory implements PayPalExpressCheckoutPaymentMethodFactoryInterface
{
    private Gateway $gateway;
    private PropertyAccessor $propertyAccessor;
    private TransactionOptionProvider $transactionOptionProvider;

    public function __construct(
        Gateway $gateway,
        PropertyAccessor $propertyAccessor,
        TransactionOptionProvider $transactionOptionProvider
    ) {
        $this->gateway = $gateway;
        $this->propertyAccessor = $propertyAccessor;
        $this->transactionOptionProvider = $transactionOptionProvider;
    }

    #[\Override]
    public function create(PayPalExpressCheckoutConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethod(
            $this->gateway,
            $config,
            $this->propertyAccessor,
            $this->transactionOptionProvider->setConfig($config)
        );
    }
}
