<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductUrlItemsProvider extends AbstractUrlItemsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass()
    {
        return Product::class;
    }
}
