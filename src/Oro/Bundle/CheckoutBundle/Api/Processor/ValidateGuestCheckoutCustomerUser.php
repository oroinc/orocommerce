<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that a customer user association is valid for an entity created by a visitor
 * when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class ValidateGuestCheckoutCustomerUser implements ProcessorInterface
{
    private GuestCheckoutChecker $guestCheckoutChecker;
    private string $associationName;

    public function __construct(
        GuestCheckoutChecker $guestCheckoutChecker,
        string $associationName = 'customerUser'
    ) {
        $this->guestCheckoutChecker = $guestCheckoutChecker;
        $this->associationName = $associationName;
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

        $customerUserForm = $context->findFormField($this->associationName);
        if (null === $customerUserForm || !$customerUserForm->isValid()) {
            return;
        }

        $customerUser = $customerUserForm->getData();
        if (!$customerUser instanceof CustomerUser) {
            return;
        }

        if ($this->isValidCustomerUser($customerUser, $this->guestCheckoutChecker->getVisitor()->getCustomerUser())) {
            return;
        }

        FormUtil::addNamedFormError(
            $customerUserForm,
            'access granted constraint',
            'No access to the entity.'
        );
    }

    private function isValidCustomerUser(CustomerUser $customerUser, ?CustomerUser $guestCustomerUser): bool
    {
        $isNewCustomerUser = null === $customerUser->getId();
        if ($isNewCustomerUser) {
            return true;
        }

        return
            null !== $guestCustomerUser
            && $customerUser->getId() === $guestCustomerUser->getId();
    }
}
