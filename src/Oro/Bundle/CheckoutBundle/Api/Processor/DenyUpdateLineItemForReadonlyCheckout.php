<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies updating line items for read-only Checkout entity.
 */
class DenyUpdateLineItemForReadonlyCheckout implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateContext $context */

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $context->getResult();
        $checkout = $lineItem->getCheckout();
        if (null === $checkout) {
            return;
        }

        if ($checkout->isCompleted()) {
            throw new AccessDeniedException('The completed checkout cannot be changed.');
        }
        if ($checkout->isPaymentInProgress()) {
            throw new AccessDeniedException('The checkout cannot be changed as the payment is being processed.');
        }
    }
}
