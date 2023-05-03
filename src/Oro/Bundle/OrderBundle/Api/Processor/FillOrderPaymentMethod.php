<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Factory\OrderPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets a specific payment method to an order if another payment method is not set yet.
 */
class FillOrderPaymentMethod implements ProcessorInterface
{
    private OrderPaymentContextFactory $paymentContextFactory;
    private ApplicablePaymentMethodsProvider $paymentMethodProvider;
    private string $paymentMethodClass;

    public function __construct(
        OrderPaymentContextFactory $paymentContextFactory,
        ApplicablePaymentMethodsProvider $paymentMethodProvider,
        string $paymentMethodClass
    ) {
        $this->paymentContextFactory = $paymentContextFactory;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentMethodClass = $paymentMethodClass;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        /** @var Order $order */
        $order = $context->getData();
        $sharedData = $context->getSharedData();
        if (PaymentOptionsContextUtil::has($sharedData, $order, PaymentOptionsContextUtil::PAYMENT_METHOD)) {
            // a payment method for the order is already set
            return;
        }

        $paymentMethod = $this->getPaymentMethod($order);
        if ($paymentMethod) {
            PaymentOptionsContextUtil::set(
                $sharedData,
                $order,
                PaymentOptionsContextUtil::PAYMENT_METHOD,
                $paymentMethod
            );
        }
    }

    private function getPaymentMethod(Order $order): ?string
    {
        $paymentMethods = $this->paymentMethodProvider->getApplicablePaymentMethods(
            $this->paymentContextFactory->create($order)
        );

        foreach ($paymentMethods as $paymentMethod) {
            if (is_a($paymentMethod, $this->paymentMethodClass)) {
                return $paymentMethod->getIdentifier();
            }
        }

        return null;
    }
}
