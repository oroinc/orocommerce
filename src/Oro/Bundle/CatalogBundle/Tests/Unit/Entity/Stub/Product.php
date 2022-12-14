<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product as BaseProduct;

class Product extends BaseProduct
{
    protected ?Category $category = null;

    protected ?float $categorySortOrder = null;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category = null)
    {
        $this->category = $category;
    }

    public function getCategorySortOrder(): ?float
    {
        return $this->categorySortOrder;
    }

    public function setCategorySortOrder(?float $categorySortOrder = null)
    {
        $this->categorySortOrder = $categorySortOrder;
    }
}
