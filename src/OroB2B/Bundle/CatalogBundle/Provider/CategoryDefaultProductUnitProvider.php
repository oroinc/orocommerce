<?php

namespace OroB2B\Bundle\CatalogBundle\Provider;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;

class CategoryDefaultProductUnitProvider implements DefaultProductUnitProviderInterface
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
        $category = $this->category;
        $data = null;
        
        while (null !== $category) {
            /** @var CategoryUnitPrecision $categoryUnitPrecision */
            $categoryUnitPrecision = null;
            if ($category->getDefaultProductOptions()) {
                $categoryUnitPrecision = $category->getDefaultProductOptions()->getUnitPrecision();
            }

            if (null !== $categoryUnitPrecision && null !== $categoryUnitPrecision->getUnit()) {
                $data = $this->createProductUnitPrecision($categoryUnitPrecision);
                break;
            }

            $category = $category->getParentCategory();
        }
        
        return $data;
    }

    /**
     * @param CategoryUnitPrecision $categoryUnitPrecision
     * @return ProductUnitPrecision
     */
    protected function createProductUnitPrecision(CategoryUnitPrecision $categoryUnitPrecision)
    {
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision
            ->setUnit($categoryUnitPrecision->getUnit())
            ->setPrecision($categoryUnitPrecision->getPrecision());

        return $productUnitPrecision;
    }
}
