<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\PrivilegeCategoryProviderInterface;

class PrivilegeCategoryProvider implements PrivilegeCategoryProviderInterface
{
    const NAME = 'catalog';

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
        return new PrivilegeCategory(self::NAME, 'oro.product.privilege.category.catalog.label', true, 1);
    }
}
