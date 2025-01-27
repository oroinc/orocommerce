<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Denies creation guest Checkout entity without a source entity.
 */
class DenyCreateForGuestCheckoutWithoutSource implements ProcessorInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $token = $this->tokenStorage->getToken();
        if (!$token instanceof AnonymousCustomerUserToken) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $context->getData();
        if (null === $checkout->getSource()?->getEntity()) {
            FormUtil::addNamedFormError(
                $context->getForm(),
                'guest checkout constraint',
                'The guest checkout must have a source entity.'
            );
        }
    }
}
