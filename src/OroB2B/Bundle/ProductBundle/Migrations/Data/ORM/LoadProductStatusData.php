<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductStatusData extends AbstractEnumFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            Product::STATUS_DISABLED => 'Disabled',
            Product::STATUS_ENABLED => 'Enabled',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'prod_status';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue()
    {
        return Product::STATUS_DISABLED;
    }
}
