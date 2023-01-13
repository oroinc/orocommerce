<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Sets the first applicable payment method for checkout.
 */
class DefaultPaymentMethodSetter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ApplicablePaymentMethodsProvider $applicablePaymentMethodsProvider;

    private CheckoutPaymentContextProvider $checkoutPaymentContextProvider;

    public function __construct(
        ApplicablePaymentMethodsProvider $applicablePaymentMethodsProvider,
        CheckoutPaymentContextProvider $checkoutPaymentContextProvider
    ) {
        $this->applicablePaymentMethodsProvider = $applicablePaymentMethodsProvider;
        $this->checkoutPaymentContextProvider = $checkoutPaymentContextProvider;
        $this->logger = new NullLogger();
    }

    public function setDefaultPaymentMethod(Checkout $checkout): void
    {
        if ($checkout->getPaymentMethod()) {
            $this->logger->debug(
                'Skipping setting default payment method for checkout because it is already set: {payment_method}',
                ['payment_method' => $checkout->getPaymentMethod(), 'checkout' => $checkout]
            );

            return;
        }

        $paymentContext = $this->checkoutPaymentContextProvider->getContext($checkout);
        if (!$paymentContext) {
            $this->logger->debug(
                'Failed to get a payment context, skipping setting default payment method for checkout',
                ['checkout' => $checkout]
            );
            return;
        }

        $paymentMethods = $this->applicablePaymentMethodsProvider->getApplicablePaymentMethods($paymentContext);
        if (!$paymentMethods) {
            $this->logger->debug(
                'Skipping setting default payment method for checkout because there are no applicable payment methods',
                ['checkout' => $checkout]
            );
            return;
        }

        $checkout->setPaymentMethod(reset($paymentMethods)->getIdentifier());
        $this->logger->debug(
            'The default payment method is set for checkout: {payment_method}',
            ['checkout' => $checkout, 'payment_method' => $checkout->getPaymentMethod()]
        );
    }
}
