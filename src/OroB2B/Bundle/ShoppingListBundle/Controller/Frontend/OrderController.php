<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class OrderController extends Controller
{
    /**
     * @Route("/create/{id}", name="orob2b_shopping_list_frontend_create_order", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_order_frontend_create")
     *
     * @param ShoppingList $shoppingList
     *
     * @return RedirectResponse
     */
    public function createAction(ShoppingList $shoppingList)
    {
        $this->get('orob2b_shopping_list.service.product_data_storage')->saveToStorage($shoppingList);

        return $this->redirectToRoute('orob2b_order_frontend_create', [ProductDataStorage::STORAGE_KEY => true]);
    }
}
