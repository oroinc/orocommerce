<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\CheckoutBundle\Api\CheckoutPaymentSubresourceProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates that a payment method is applicable for a checkout payment sub-resource.
 */
class ValidateCheckoutPaymentMethodIsApplicable implements ProcessorInterface
{
    public function __construct(
        private readonly CheckoutPaymentSubresourceProviderInterface $checkoutPaymentSubresourceProvider
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        /** @var Checkout $checkout */
        $checkout = $context->getParentEntity();
        $paymentMethod = $checkout->getPaymentMethod();
        if (!$paymentMethod
            || !$this->checkoutPaymentSubresourceProvider->isSupportedPaymentMethod($paymentMethod)
        ) {
            throw new AccessDeniedException('The payment method is not supported.');
        }
    }
}
