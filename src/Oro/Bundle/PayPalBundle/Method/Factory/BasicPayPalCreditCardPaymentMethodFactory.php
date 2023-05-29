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
    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * @var RouterInterface
     */
    private $router;

    private TransactionOptionProvider $transactionOptionProvider;

    public function __construct(
        Gateway $gateway,
        RouterInterface $router,
    ) {
        $this->gateway = $gateway;
        $this->router = $router;
    }

    public function setTransactionOptionProvider(TransactionOptionProvider $transactionOptionProvider): void
    {
        $this->transactionOptionProvider = $transactionOptionProvider;
    }

    /**
     * @param PayPalCreditCardConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(PayPalCreditCardConfigInterface $config)
    {
        $method = new PayPalCreditCardPaymentMethod(
            $this->gateway,
            $config,
            $this->router,
        );
        $method->setTransactionOptionProvider($this->transactionOptionProvider);

        return $method;
    }
}
