<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Fixture\AbstractEnumFixture;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductVisibilityData extends AbstractEnumFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            Product::VISIBILITY_BY_CONFIG   => 'As Defined in System Configuration',
            Product::VISIBILITY_VISIBLE     => 'Yes',
            Product::VISIBILITY_NOT_VISIBLE => 'No'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassName()
    {
        return ExtendHelper::buildEnumValueClassName('prod_visibility');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue()
    {
        return Product::VISIBILITY_BY_CONFIG;
    }
}
