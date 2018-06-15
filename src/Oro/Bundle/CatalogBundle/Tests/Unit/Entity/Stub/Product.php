<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\ProductBundle\Entity\Product as BaseProduct;
use Oro\Bundle\CatalogBundle\Entity\Category;

class Product extends BaseProduct
{
    /**
     * @var Category
     */
    protected $category;

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;
    }
}
