<?php

namespace OroB2B\Bundle\CatalogBundle\Provider;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Provider\AbstractDefaultProductUnitProvider;

class CategoryDefaultProductUnitProvider extends AbstractDefaultProductUnitProvider
{
    /**
     * @var Category
     */
    protected $category;

    /**
     * @param Category $category
     */
    public function setCategory(Category $category = null)
    {
        $this->category = $category;
    }

    /**
     * @return ProductUnitPrecision|null
     */
    public function getDefaultProductUnitPrecision()
    {
        if (!$this->category) {
            return null;
        } else {
            do {
                /** @var CategoryUnitPrecision $categoryUnitPrecision */
                $categoryUnitPrecision = null;
                if ($this->category->getDefaultProductOptions()) {
                    $categoryUnitPrecision = $this->category->getDefaultProductOptions()->getUnitPrecision();
                }

                if (null !== $categoryUnitPrecision && null !== $categoryUnitPrecision->getUnit()) {
                    return $this->createProductUnitPrecision(
                        $categoryUnitPrecision->getUnit(),
                        $categoryUnitPrecision->getPrecision()
                    );
                }
                $this->category = $this->category->getParentCategory();
            } while (null !== $this->category);
        }
        return null;
    }
}
