<?php

namespace Oro\Bundle\ShoppingListBundle\Provider;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\PrivilegeCategoryProviderInterface;

class PrivilegeCategoryProvider implements PrivilegeCategoryProviderInterface
{
    const NAME = 'shopping';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getRolePrivilegeCategory()
    {
        return new PrivilegeCategory(self::NAME, 'oro.shoppinglist.privilege.category.shopping.label', true, 1);
    }
}
