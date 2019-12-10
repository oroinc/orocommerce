<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Factory\OrderPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets a specific payment method to an order if another payment method is not set yet.
 */
class FillOrderPaymentMethod implements ProcessorInterface
{
    /** @var OrderPaymentContextFactory */
    private $paymentContextFactory;

    /** @var PaymentMethodProvider */
    private $paymentMethodProvider;

    /** @var string */
    private $paymentMethodClass;

    /**
     * @param OrderPaymentContextFactory $paymentContextFactory
     * @param PaymentMethodProvider      $paymentMethodProvider
     * @param string                     $paymentMethodClass
     */
    public function __construct(
        OrderPaymentContextFactory $paymentContextFactory,
        PaymentMethodProvider $paymentMethodProvider,
        string $paymentMethodClass
    ) {
        $this->paymentContextFactory = $paymentContextFactory;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentMethodClass = $paymentMethodClass;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
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

    /**
     * @param Order $order
     *
     * @return string|null
     */
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
