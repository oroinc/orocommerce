<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Adds the new shopping list to current visitor.
 * This processor is required because the association between shopping list and visitor
 * is unidirectional association on the visitor side and as result, it is not set automatically.
 */
class AddNewShoppingListToVisitor implements ProcessorInterface
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $token = $this->tokenStorage->getToken();
        if (!$token instanceof AnonymousCustomerUserToken) {
            return;
        }

        $form = $context->getForm();
        if (!$form->isValid()) {
            return;
        }

        $shoppingList = $context->getData();
        $visitor = $token->getVisitor();
        $visitor->addShoppingList($shoppingList);
    }
}
