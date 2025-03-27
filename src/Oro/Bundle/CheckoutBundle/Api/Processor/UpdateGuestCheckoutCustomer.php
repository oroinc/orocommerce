<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates a created customer with data submited for customer user
 * when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class UpdateGuestCheckoutCustomer implements ProcessorInterface
{
    public function __construct(
        private readonly GuestCheckoutChecker $guestCheckoutChecker
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return;
        }

        /** @var CustomerUser $customerUser */
        $customerUser = $context->getData();
        $customer = $customerUser->getCustomer();
        if (null !== $customer) {
            $customerUser->fillCustomer();
        }
    }
}
