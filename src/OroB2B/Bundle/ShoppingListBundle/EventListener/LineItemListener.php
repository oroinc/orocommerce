<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Component\DependencyInjection\ServiceLink;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class LineItemListener
{
    /**
     * @var ServiceLink
     */
    protected $shoppingListManagerLink;

    /**
     * @param ServiceLink $shoppingListManagerLink
     */
    public function __construct(ServiceLink $shoppingListManagerLink)
    {
        $this->shoppingListManagerLink = $shoppingListManagerLink;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        /** @var LineItem $lineItem */
        $lineItem = $args->getEntity();
        if ($lineItem instanceof LineItem) {
            $this->getShoppingListManager()->recalculateSubtotals($lineItem->getShoppingList());
        }
    }

    /**
     * @return ShoppingListManager
     */
    protected function getShoppingListManager()
    {
        return $this->shoppingListManagerLink->getService();
    }
}
