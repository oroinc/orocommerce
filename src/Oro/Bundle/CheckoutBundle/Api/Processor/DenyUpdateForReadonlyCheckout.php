<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies updating read-only Checkout entity.
 */
class DenyUpdateForReadonlyCheckout implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateContext $context */

        /** @var Checkout $checkout */
        $checkout = $context->getResult();
        if ($checkout->isCompleted()) {
            throw new AccessDeniedException('The completed checkout cannot be changed.');
        }
        if ($checkout->isPaymentInProgress()) {
            throw new AccessDeniedException('The checkout cannot be changed as the payment is being processed.');
        }
    }
}
