<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Provider;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 *
 * Determines which actions (Edit/View) are available for a shopping list in the Datagrid.
 */
class ShoppingListActionPermissionProvider
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function getShoppingListPermissions(ResultRecordInterface $record): array
    {
        return [
            'update' => $this->authorizationChecker->isGranted('oro_shopping_list_frontend_update'),
            'view' => !$this->authorizationChecker->isGranted('oro_shopping_list_frontend_update')
                && $this->authorizationChecker->isGranted('oro_shopping_list_frontend_view'),
        ];
    }
}
