<?php

namespace Oro\Bundle\ShoppingListBundle\Controller;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * View shopping lists in back office
 */
class ShoppingListController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="oro_shopping_list_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_shopping_list_view",
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
            'totals' => $this->get(TotalProcessorProvider::class)->getTotalWithSubtotalsAsArray($shoppingList)
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_shopping_list_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_shopping_list_view")
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
     * @Route("/", name="oro_shopping_list_index")
     * @Template
     * @AclAncestor("oro_shopping_list_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => ShoppingList::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            TotalProcessorProvider::class,
        ]);
    }
}
