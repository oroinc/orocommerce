<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType\Stub;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\WebCatalog\Test\Unit\Form\Type\AbstractContentVariantStub;

class ContentVariantStub extends AbstractContentVariantStub
{
    /**
     * @var Category
     */
    protected $categoryPageCategory;

    /**
     * @var bool
     */
    protected $excludeSubcategories;

    /**
     * @return Category
     */
    public function getCategoryPageCategory()
    {
        return $this->categoryPageCategory;
    }

    /**
     * @param Category $category
     * @return ContentVariantStub
     */
    public function setCategoryPageCategory(Category $category)
    {
        $this->categoryPageCategory = $category;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExcludeSubcategories()
    {
        return $this->excludeSubcategories;
    }

    /**
     * @param bool $excludeSubcategories
     * @return ContentVariantStub
     */
    public function setExcludeSubcategories($excludeSubcategories)
    {
        $this->excludeSubcategories = $excludeSubcategories;

        return $this;
    }
}
