<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListNotesType;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use Symfony\Component\Form\FormView;

/**
 * Provides form types to edit shopping list.
 */
class ShoppingListFormProvider extends AbstractFormProvider
{
    const SHOPPING_LIST_CREATE_ROUTE_NAME   = 'oro_shopping_list_frontend_create';

    /**
     * @param ShoppingList $shoppingList
     *
     * @return FormView
     */
    public function getShoppingListFormView(ShoppingList $shoppingList)
    {
        $options['action'] = $this->generateUrl(
            self::SHOPPING_LIST_CREATE_ROUTE_NAME
        );

        return $this->getFormView(ShoppingListType::class, $shoppingList, $options);
    }

    public function getShoppingListNotesFormView(ShoppingList $shoppingList): FormView
    {
        return $this->getFormView(ShoppingListNotesType::class, $shoppingList);
    }
}
