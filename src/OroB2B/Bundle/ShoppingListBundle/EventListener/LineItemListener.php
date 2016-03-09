<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class LineItemListener
{
    /**
     * @var ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @param ShoppingListManager $shoppingListManager
     */
    public function __construct(ShoppingListManager $shoppingListManager)
    {
        $this->shoppingListManager = $shoppingListManager;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        /** @var LineItem $lineItem */
        $lineItem = $args->getEntity();
        if ($lineItem instanceof LineItem) {
            $this->shoppingListManager->recalculateSubtotals($lineItem->getShoppingList());
        }
    }
}
