<?php

namespace Oro\Bundle\ShoppingListBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_shopping_list_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_shopping_list_view",
     *      type="entity",
     *      class="OroShoppingListBundle:ShoppingList",
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
            'entity' => $shoppingList,
            'totals' => $this->getTotalProcessor()->getTotalWithSubtotalsAsArray($shoppingList)
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
            'entity_class' => $this->container->getParameter('orob2b_shopping_list.entity.shopping_list.class')
        ];
    }

    /**
     * @return TotalProcessorProvider
     */
    protected function getTotalProcessor()
    {
        return $this->get('orob2b_pricing.subtotal_processor.total_processor_provider');
    }
}
