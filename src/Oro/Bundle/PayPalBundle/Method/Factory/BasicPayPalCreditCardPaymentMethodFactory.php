<?php

namespace Oro\Bundle\PayPalBundle\Method\Factory;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod;
use Oro\Bundle\PayPalBundle\Method\Transaction\TransactionOptionProvider;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\Routing\RouterInterface;

/**
 * Factory to create instance of PayPal credit card payment method.
 */
class BasicPayPalCreditCardPaymentMethodFactory implements PayPalCreditCardPaymentMethodFactoryInterface
{
    private Gateway $gateway;
    private RouterInterface $router;
    private TransactionOptionProvider $transactionOptionProvider;

    public function __construct(
        Gateway $gateway,
        RouterInterface $router,
        TransactionOptionProvider $transactionOptionProvider,
    ) {
        $this->gateway = $gateway;
        $this->router = $router;
        $this->transactionOptionProvider = $transactionOptionProvider;
    }

    /**
     * @param PayPalCreditCardConfigInterface $config
     * @return PaymentMethodInterface
     */
    #[\Override]
    public function create(PayPalCreditCardConfigInterface $config)
    {
        return new PayPalCreditCardPaymentMethod(
            $this->gateway,
            $config,
            $this->router,
            $this->transactionOptionProvider,
        );
    }
}
