<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\PrivilegeCategoryProviderInterface;

/**
 * Provides CMS category as a tab in user roles.
 */
class PrivilegeCategoryProvider implements PrivilegeCategoryProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cms';
    }

    /**
     * {@inheritdoc}
     */
    public function getRolePrivilegeCategory()
    {
        return new PrivilegeCategory('cms', 'oro.cms.privilege.category.cms.label', true, 6);
    }
}
