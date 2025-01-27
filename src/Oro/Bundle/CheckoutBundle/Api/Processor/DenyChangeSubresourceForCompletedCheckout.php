<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies subresources that change data for completed Checkout entity.
 */
class DenyChangeSubresourceForCompletedCheckout implements ProcessorInterface
{
    public const OPERATION_NAME = 'deny_change_subresource_for_completed_checkout';

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $context->getParentEntity();
        if ($checkout->isCompleted()) {
            throw new AccessDeniedException('The completed checkout cannot be changed.');
        }
        $context->setProcessed(self::OPERATION_NAME);
    }
}
