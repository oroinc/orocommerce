<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface;

class PaymentResultMessageProvider implements PaymentResultMessageProviderInterface
{
    /**
     * @var PaymentMethodProvider
     */
    protected $paymentMethodProvider;

    public function __construct(PaymentMethodProvider $paymentMethodProvider)
    {
        $this->paymentMethodProvider = $paymentMethodProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorMessage(PaymentTransaction $transaction = null)
    {
        if (!$transaction) {
            return 'oro.checkout.errors.payment.error_single_method';
        }

        $applicablePaymentMethods = $this->paymentMethodProvider->getApplicablePaymentMethods($transaction);
        if ($applicablePaymentMethods && count($applicablePaymentMethods) > 1) {
            return 'oro.checkout.errors.payment.error_multiple_methods';
        }

        return 'oro.checkout.errors.payment.error_single_method';
    }
}
