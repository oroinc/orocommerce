<?php

namespace Oro\Bundle\PaymentTermBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\CheckoutBundle\Api\Processor\AbstractHandlePaymentSubresource;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

/**
 * Handles the checkout Payment Term payment sub-resource.
 */
class HandlePaymentTermPaymentSubresource extends AbstractHandlePaymentSubresource
{
    #[\Override]
    protected function getInProgressStatuses(): array
    {
        return [];
    }

    #[\Override]
    protected function getErrorStatuses(): array
    {
        return [
            PaymentStatusProvider::CANCELED,
            PaymentStatusProvider::DECLINED
        ];
    }

    #[\Override]
    protected function getPaymentTransactionOptions(
        Checkout $checkout,
        ChangeSubresourceContext $context
    ): array {
        return [];
    }

    #[\Override]
    protected function processPaymentError(
        Checkout $checkout,
        Order $order,
        array $paymentResult,
        ChangeSubresourceContext $context
    ): void {
        $this->onPaymentError($checkout, $context);
        $this->saveChanges($context);
        $context->addError(Error::createValidationError(
            'payment constraint',
            'Payment failed, please try again or select a different payment method.'
        ));
    }
}
