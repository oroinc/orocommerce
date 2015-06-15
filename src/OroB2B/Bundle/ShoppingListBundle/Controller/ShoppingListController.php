<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

class ShoppingListController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_shopping_list_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_shopping_list_view",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:ShoppingList",
     *      permission="VIEW"
     * )
     *
     * @param ShoppingList $shoppingList
     *
     * @return array
     */
    public function viewAction(ShoppingList $shoppingList)
    {
        return [
            'entity' => $shoppingList
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_shopping_list_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_shopping_list_view")
     *
     * @param ShoppingList $shoppingList
     *
     * @return array
     */
    public function infoAction(ShoppingList $shoppingList)
    {
        return [
            'shopping_list' => $shoppingList
        ];
    }

    /**
     * @Route("/", name="orob2b_shopping_list_index")
     * @Template
     * @AclAncestor("orob2b_shopping_list_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_shopping_list.shopping_list.class')
        ];
    }
}
