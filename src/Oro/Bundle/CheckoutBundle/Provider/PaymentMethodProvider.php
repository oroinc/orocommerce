<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider as PaymentBundleMethodProvider;

/**
 * Provides applicable payment methods for given payment transaction.
 */
class PaymentMethodProvider
{
    /**
     * @var CheckoutPaymentContextProvider
     */
    protected $checkoutPaymentContextProvider;

    /**
     * @var CheckoutRepository
     */
    protected $checkoutRepository;

    /**
     * @var PaymentBundleMethodProvider
     */
    protected $paymentMethodProvider;

    public function __construct(
        CheckoutPaymentContextProvider $checkoutPaymentContextProvider,
        CheckoutRepository $checkoutRepository,
        PaymentBundleMethodProvider $paymentMethodProvider
    ) {
        $this->checkoutPaymentContextProvider = $checkoutPaymentContextProvider;
        $this->checkoutRepository = $checkoutRepository;
        $this->paymentMethodProvider = $paymentMethodProvider;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return null|PaymentMethodInterface[]
     */
    public function getApplicablePaymentMethods(PaymentTransaction $paymentTransaction)
    {
        $transactionOptions = $paymentTransaction->getTransactionOptions();
        if (empty($transactionOptions['checkoutId'])) {
            return null;
        }

        /** @var Checkout|null $checkout */
        $checkout = $this->checkoutRepository->find($transactionOptions['checkoutId']);
        if (!$checkout) {
            return null;
        }

        $context = $this->checkoutPaymentContextProvider->getContext($checkout);
        if (!$context) {
            return null;
        }

        return $this->paymentMethodProvider->getApplicablePaymentMethods($context);
    }
}
