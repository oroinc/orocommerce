<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class ShoppingListFormProvider extends AbstractFormProvider
{
    const SHOPPING_LIST_CREATE_ROUTE_NAME   = 'oro_shopping_list_frontend_create';
    const SHOPPING_LIST_VIEW_ROUTE_NAME     = 'oro_shopping_list_frontend_view';

    /**
     * @param ShoppingList $shoppingList
     * @param array        $options
     *
     * @return FormInterface
     */
    public function getShoppingListForm(ShoppingList $shoppingList, array $options = [])
    {
        if ($shoppingList->getId()) {
            $options['action'] = $this->generateUrl(
                self::SHOPPING_LIST_VIEW_ROUTE_NAME,
                ['id' => $shoppingList->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::SHOPPING_LIST_CREATE_ROUTE_NAME
            );
        }

        return $this->getForm(ShoppingListType::NAME, $shoppingList, $options);
    }
}
