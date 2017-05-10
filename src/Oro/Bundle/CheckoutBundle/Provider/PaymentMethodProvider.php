<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider as PaymentBundleMethodProvider;

class PaymentMethodProvider
{
    /**
     * @var CheckoutPaymentContextFactory
     */
    protected $contextFactory;

    /**
     * @var CheckoutRepository
     */
    protected $checkoutRepository;

    /**
     * @var PaymentBundleMethodProvider
     */
    protected $paymentMethodProvider;

    /**
     * @param CheckoutPaymentContextFactory $contextFactory
     * @param CheckoutRepository $checkoutRepository
     * @param PaymentBundleMethodProvider $paymentMethodProvider
     */
    public function __construct(
        CheckoutPaymentContextFactory $contextFactory,
        CheckoutRepository $checkoutRepository,
        PaymentBundleMethodProvider $paymentMethodProvider
    ) {
        $this->contextFactory = $contextFactory;
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

        $context = $this->contextFactory->create($checkout);
        if (!$context) {
            return null;
        }

        return $this->paymentMethodProvider->getApplicablePaymentMethods($context);
    }
}
