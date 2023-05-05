<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables security checks when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class DisableGuestCheckoutSecurityCheck implements ProcessorInterface
{
    private GuestCheckoutChecker $guestCheckoutChecker;

    public function __construct(GuestCheckoutChecker $guestCheckoutChecker)
    {
        $this->guestCheckoutChecker = $guestCheckoutChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return;
        }

        $context->skipGroup(ApiActionGroup::SECURITY_CHECK);
    }
}
