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
        return [
            self::ROLE_BASIC,
            self::ROLE_LOCAL,
            self::ROLE_LOCAL_VIEW_ONLY,
            self::ROLE_DEEP,
            self::ROLE_DEEP_VIEW_ONLY,
        ];
    }
}
