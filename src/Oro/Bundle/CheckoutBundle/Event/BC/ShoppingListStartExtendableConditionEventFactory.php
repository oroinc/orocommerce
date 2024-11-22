<?php

namespace Oro\Bundle\CheckoutBundle\Event\BC;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableConditionEventFactoryInterface;
use Oro\Component\Action\Event\ExtendableEventData;

/**
 * Creates ExtendableConditionEvent and fills shoppingList data.
 */
class ShoppingListStartExtendableConditionEventFactory implements ExtendableConditionEventFactoryInterface
{
    public function createEvent($context): ExtendableConditionEvent
    {
        $event = new ExtendableConditionEvent($context);

        if ($context instanceof WorkflowItem) {
            // Create new ExtendableEventData based on the storage data to prevent original data modification.
            $data = new ExtendableEventData($context->getActionDataStorage()->toArray());
            $data->offsetSet('shoppingList', $context->getResult()->offsetGet('shoppingList'));
            $event->setData($data);
        }

        return $event;
    }
}
