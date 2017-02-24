<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryUrlItemsProvider extends AbstractUrlItemsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass()
    {
        return Category::class;
    }
}
