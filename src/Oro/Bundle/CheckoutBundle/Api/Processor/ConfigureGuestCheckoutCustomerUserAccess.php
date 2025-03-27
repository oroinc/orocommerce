<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Bundle\CheckoutBundle\Api\Validator\GuestCheckoutCustomerUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Replaces AccessGranted validation constraint with GuestCheckoutCustomerUser validation constraint
 * for "customerUser" association when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class ConfigureGuestCheckoutCustomerUserAccess implements ProcessorInterface
{
    public function __construct(
        private readonly GuestCheckoutChecker $guestCheckoutChecker
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return;
        }

        $definition = $context->getResult();
        $customerUserField = $definition->findField('customerUser', true);
        if (null !== $customerUserField) {
            $customerUserField->removeFormConstraint(AccessGranted::class);
            $customerUserField->addFormConstraint(new GuestCheckoutCustomerUser(['groups' => ['api']]));
        }
    }
}
