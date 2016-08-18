<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use Oro\Component\Layout\DataProvider\AbstractFormProvider;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class ShoppingListFormProvider extends AbstractFormProvider
{
    const SHOPPING_LIST_CREATE_ROUTE_NAME   = 'orob2b_shopping_list_frontend_create';
    const SHOPPING_LIST_VIEW_ROUTE_NAME     = 'orob2b_shopping_list_frontend_view';

    /**
     * @param ShoppingList $shoppingList
     *
     * @return FormAccessor
     */
    public function getShoppingListForm(ShoppingList $shoppingList)
    {
        if ($shoppingList->getId()) {
            return $this->getFormAccessor(
                ShoppingListType::NAME,
                self::SHOPPING_LIST_VIEW_ROUTE_NAME,
                $shoppingList,
                ['id' => $shoppingList->getId()]
            );
        }

        return $this->getFormAccessor(
            ShoppingListType::NAME,
            self::SHOPPING_LIST_CREATE_ROUTE_NAME,
            $shoppingList
        );
    }
}
