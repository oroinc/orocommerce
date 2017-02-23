<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractLineItemController extends Controller
{
    /**
     * @param ShoppingList $shoppingList
     * @param string $translationKey
     * @return string
     */
    protected function getSuccessMessage(ShoppingList $shoppingList, $translationKey)
    {
        $link = $this->get('router')->generate('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()]);

        $translator = $this->get('translator');
        $label = htmlspecialchars($shoppingList->getLabel());

        return $translator->trans(
            $translationKey,
            ['%shoppinglist%' => sprintf('<a href="%s">%s</a>', $link, $label)]
        );
    }
}
