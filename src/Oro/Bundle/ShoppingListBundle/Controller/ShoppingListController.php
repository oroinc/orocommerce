<?php

namespace Oro\Bundle\ShoppingListBundle\Controller;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
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
     * @param ShoppingList $shoppingList
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_shopping_list_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_shopping_list_view', type: 'entity', class: ShoppingList::class, permission: 'VIEW')]
    public function viewAction(ShoppingList $shoppingList)
    {
        return [
            'entity' => $shoppingList,
            'totals' => $this->container->get(TotalProcessorProvider::class)
                ->getTotalWithSubtotalsAsArray($shoppingList)
        ];
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    #[Route(path: '/info/{id}', name: 'oro_shopping_list_info', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_shopping_list_view')]
    public function infoAction(ShoppingList $shoppingList)
    {
        return [
            'shopping_list' => $shoppingList
        ];
    }

    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_shopping_list_index')]
    #[Template]
    #[AclAncestor('oro_shopping_list_view')]
    public function indexAction()
    {
        return [
            'entity_class' => ShoppingList::class,
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            TotalProcessorProvider::class,
        ]);
    }
}
