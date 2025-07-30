<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendLineItemsGrid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies access to shopping list line items if user have no access to the Shopping List.
 */
class ShoppingListLineItemsAccessListener
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ManagerRegistry $registry
    ) {
    }

    public function onBuildBefore(BuildBefore $event)
    {
        $shoppingList = $this->registry->getRepository(ShoppingList::class)->find(
            $event->getDatagrid()->getParameters()->get('shopping_list_id')
        );

        if (!$this->authorizationChecker->isGranted('VIEW', $shoppingList)) {
            throw new AccessDeniedException();
        }
    }
}
