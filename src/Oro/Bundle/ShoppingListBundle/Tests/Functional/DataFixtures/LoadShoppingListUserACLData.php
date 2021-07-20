<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\AbstractLoadACLData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingListUserACLData extends AbstractLoadACLData
{
    /**
     * @return string
     */
    protected function getAclResourceClassName()
    {
        return ShoppingList::class;
    }

    /**
     * @return array
     */
    protected function getSupportedRoles()
    {
        return array_keys($this->getRolesAndPermissions());
    }

    protected function getRolesAndPermissions(): array
    {
        return [
            static::ROLE_BASIC => [
                'VIEW_BASIC',
                'CREATE_BASIC',
                'EDIT_BASIC',
                'DELETE_BASIC',
                'SET_AS_DEFAULT_SHOPPING_LIST_BASIC',
            ],
            static::ROLE_LOCAL => [
                'VIEW_LOCAL',
                'CREATE_LOCAL',
                'EDIT_LOCAL',
                'DELETE_LOCAL',
                'ASSIGN_LOCAL',
                'SET_AS_DEFAULT_SHOPPING_LIST_LOCAL',
            ],
            static::ROLE_LOCAL_VIEW_ONLY => ['VIEW_LOCAL'],
            static::ROLE_DEEP => [
                'VIEW_DEEP',
                'CREATE_DEEP',
                'EDIT_DEEP',
                'DELETE_DEEP',
                'ASSIGN_DEEP',
                'SET_AS_DEFAULT_SHOPPING_LIST_DEEP',
            ],
            static::ROLE_DEEP_VIEW_ONLY => ['VIEW_DEEP'],
        ];
    }
}
