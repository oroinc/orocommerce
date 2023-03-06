<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables NotBlank validation constraint for firstName and lastName fields of customer user
 * when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class DisableGuestCheckoutCustomerUserNameValidation implements ProcessorInterface
{
    private const FIRST_NAME_FAKE_DATA_FLAG = '_guest_checkout_fake_data_first_name';
    private const LAST_NAME_FAKE_DATA_FLAG = '_guest_checkout_fake_data_last_name';

    private const FAKE_NAME = '-';

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

        switch ($context->getEvent()) {
            case CustomizeFormDataContext::EVENT_PRE_VALIDATE:
                $this->processPreValidate($context);
                break;
            case CustomizeFormDataContext::EVENT_POST_VALIDATE:
                $this->processPostValidate($context);
                break;
        }
    }

    private function processPreValidate(CustomizeFormDataContext $context): void
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $context->getData();
        if (!$customerUser->getFirstName()) {
            $context->set(self::FIRST_NAME_FAKE_DATA_FLAG, true);
            $customerUser->setFirstName(self::FAKE_NAME);
        }
        if (!$customerUser->getLastName()) {
            $context->set(self::LAST_NAME_FAKE_DATA_FLAG, true);
            $customerUser->setLastName(self::FAKE_NAME);
        }
    }

    private function processPostValidate(CustomizeFormDataContext $context): void
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $context->getData();
        if ($context->has(self::FIRST_NAME_FAKE_DATA_FLAG)) {
            $context->remove(self::FIRST_NAME_FAKE_DATA_FLAG);
            $customerUser->setFirstName(null);
        }
        if ($context->has(self::LAST_NAME_FAKE_DATA_FLAG)) {
            $context->remove(self::LAST_NAME_FAKE_DATA_FLAG);
            $customerUser->setLastName(null);
        }
    }
}
