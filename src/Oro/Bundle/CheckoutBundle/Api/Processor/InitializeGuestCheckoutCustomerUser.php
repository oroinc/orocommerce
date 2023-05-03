<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Fills a new customer user with a guest specific data
 * when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class InitializeGuestCheckoutCustomerUser implements ProcessorInterface
{
    private GuestCheckoutChecker $guestCheckoutChecker;
    private GuestCustomerUserManager $guestCustomerUserManager;

    public function __construct(
        GuestCheckoutChecker $guestCheckoutChecker,
        GuestCustomerUserManager $guestCustomerUserManager
    ) {
        $this->guestCheckoutChecker = $guestCheckoutChecker;
        $this->guestCustomerUserManager = $guestCustomerUserManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return;
        }

        if (!$context->hasResult()) {
            return;
        }

        $this->guestCustomerUserManager->initializeGuestCustomerUser($context->getResult());
    }
}
