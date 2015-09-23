<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;

class LoadCategoryVisibilityData extends AbstractEnumFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            CategoryVisibility::PARENT_CATEGORY => 'Parent Category',
            CategoryVisibility::CONFIG => 'Config',
            CategoryVisibility::HIDDEN => 'Hidden',
            CategoryVisibility::VISIBLE => 'Visible',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'category_visibility';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue()
    {
        return CategoryVisibility::PARENT_CATEGORY;
    }
}
