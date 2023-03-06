<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Ensures that a customer user is assigned to the processing line item.
 * If a customer user is not assigned to the line item,
 * the shopping list's customer user will be assigned to it.
 */
class CompleteNewLineItem implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var LineItem $lineItem */
        $lineItem = $context->getData();
        $shoppingList = $lineItem->getShoppingList();
        if (null !== $shoppingList
            && null === $lineItem->getCustomerUser()
            && null !== $shoppingList->getCustomerUser()
        ) {
            $lineItem->setCustomerUser($shoppingList->getCustomerUser());
        }
    }
}
