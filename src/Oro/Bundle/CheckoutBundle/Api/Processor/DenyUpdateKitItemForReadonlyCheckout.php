<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies updating line item kit items for read-only Checkout entity.
 */
class DenyUpdateKitItemForReadonlyCheckout implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateContext $context */

        /** @var CheckoutProductKitItemLineItem $kitItem */
        $kitItem = $context->getResult();
        $checkout = $kitItem->getLineItem()?->getCheckout();
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
