<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Validator\Constraints\OrderAmountLimits;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates that the shopping list satisfies the configured minimum and maximum order amount
 * when starting a checkout from a shopping list.
 */
class ValidateShoppingListOrderAmountLimitsEventListener
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    public function onStartFromShoppingList(ExtendableConditionEvent $event): void
    {
        $shoppingList = $event->getData()?->offsetGet('shoppingList');
        if (!$shoppingList instanceof ShoppingList) {
            return;
        }

        $violations = $this->validator->validate($shoppingList, new OrderAmountLimits());
        foreach ($violations as $violation) {
            $event->addError($violation->getMessage(), $violation);
        }
    }
}
