<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;

class LoadAccountCategoryVisibilityData extends AbstractEnumFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            AccountCategoryVisibility::ACCOUNT_GROUP => 'Account Group',
            AccountCategoryVisibility::CATEGORY => 'Visibility to All',
            AccountCategoryVisibility::PARENT_CATEGORY => 'Parent Category',
            AccountCategoryVisibility::HIDDEN => 'Hidden',
            AccountCategoryVisibility::VISIBLE => 'Visible',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'acc_ctgry_visibility';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue()
    {
        return AccountCategoryVisibility::ACCOUNT_GROUP;
    }
}
