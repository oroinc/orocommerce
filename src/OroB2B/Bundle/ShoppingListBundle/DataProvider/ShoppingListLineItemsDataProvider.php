<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListLineItemsDataProvider
{
    /**
     * @var array
     */
    protected $lineItems = [];

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return \Oro\Bundle\ShoppingListBundle\Entity\LineItem[]
     */
    public function getShoppingListLineItems(ShoppingList $shoppingList)
    {
        $shoppingListId = $shoppingList->getId();
        if (array_key_exists($shoppingListId, $this->lineItems)) {
            return $this->lineItems[$shoppingListId];
        }
        /** @var LineItemRepository $repository */
        $repository = $this->registry->getManagerForClass('OroShoppingListBundle:LineItem')
            ->getRepository('OroShoppingListBundle:LineItem');
        $lineItems = $repository->getItemsWithProductByShoppingList($shoppingList);
        $this->lineItems[$shoppingListId] = $lineItems;
        return $lineItems;
    }
}
