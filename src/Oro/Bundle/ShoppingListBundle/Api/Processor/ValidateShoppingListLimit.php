<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that a shopping list can be created.
 * E.g. it can be not allowed due to the shopping list limit for a customer is exceeded.
 */
class ValidateShoppingListLimit implements ProcessorInterface
{
    private FeatureChecker $featureChecker;
    private GuestShoppingListManager $guestShoppingListManager;
    private TokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;

    public function __construct(
        FeatureChecker $featureChecker,
        GuestShoppingListManager $guestShoppingListManager,
        TokenStorageInterface    $tokenStorage,
        TranslatorInterface $translator
    ) {
        $this->featureChecker = $featureChecker;
        $this->guestShoppingListManager = $guestShoppingListManager;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        if (!$form->isValid()) {
            return;
        }

        if ($this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
            if ($this->guestShoppingListManager->findExistingShoppingListForCustomerVisitor()) {
                $this->addCreateNotAllowedConstraint($form);
            }
        } elseif (!$this->featureChecker->isFeatureEnabled('shopping_list_create')) {
            $this->addCreateNotAllowedConstraint($form);
        }
    }

    private function addCreateNotAllowedConstraint(FormInterface $form): void
    {
        FormUtil::addNamedFormError(
            $form,
            'create shopping list constraint',
            $this->translator->trans('oro.shoppinglist.create_not_allowed', [], 'validators')
        );
    }
}
