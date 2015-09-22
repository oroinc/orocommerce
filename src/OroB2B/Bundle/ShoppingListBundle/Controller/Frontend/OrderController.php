<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Controller\OrderController as BaseOrderController;

class OrderController extends BaseOrderController
{
    /**
     * @Route("/create/{id}", name="orob2b_shopping_list_frontend_create_order", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_order_frontend_create")
     *
     * @param ShoppingList $shoppingList
     *
     * @return array
     */
    public function createAction(ShoppingList $shoppingList)
    {
        $this->saveToStorage($shoppingList);

        return $this->redirect(
            $this->generateUrl('orob2b_order_frontend_create', [ProductDataStorage::STORAGE_KEY => true])
        );
    }
}
