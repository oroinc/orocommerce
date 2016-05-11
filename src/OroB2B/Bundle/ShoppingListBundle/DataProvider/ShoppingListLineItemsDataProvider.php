<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataProvider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

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
     * @return \OroB2B\Bundle\ShoppingListBundle\Entity\LineItem[]
     */
    public function getShoppingListLineItems(ShoppingList $shoppingList)
    {
        $shoppingListId = $shoppingList->getId();
        if (array_key_exists($shoppingListId, $this->lineItems)) {
            return $this->lineItems[$shoppingListId];
        }
        /** @var LineItemRepository $repository */
        $repository = $this->registry->getManagerForClass('OroB2BShoppingListBundle:LineItem')
            ->getRepository('OroB2BShoppingListBundle:LineItem');
        $lineItems = $repository->getItemsWithProductByShoppingList($shoppingList);
        $this->lineItems[$shoppingListId] = $lineItems;
        return $lineItems;
    }
}
