<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Links a customer user to a visitor from the current security context
 * when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class LinkGuestCheckoutCustomerUserToVisitor implements ProcessorInterface
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
        /** @var CustomizeFormDataContext $context */

        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return;
        }

        if (!$context->getForm()->isValid()) {
            return;
        }

        $this->guestCheckoutChecker->getVisitor()->setCustomerUser($context->getData());
    }
}
