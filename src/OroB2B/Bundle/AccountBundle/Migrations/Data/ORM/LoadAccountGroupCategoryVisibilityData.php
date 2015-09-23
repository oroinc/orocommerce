<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;

class LoadAccountGroupCategoryVisibilityData extends AbstractEnumFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            AccountGroupCategoryVisibility::CATEGORY => 'Visibility to All',
            AccountGroupCategoryVisibility::PARENT_CATEGORY => 'Parent Category',
            AccountGroupCategoryVisibility::HIDDEN => 'Hidden',
            AccountGroupCategoryVisibility::VISIBLE => 'Visible',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'acc_grp_ctgry_vsblity';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue()
    {
        return AccountGroupCategoryVisibility::CATEGORY;
    }
}
