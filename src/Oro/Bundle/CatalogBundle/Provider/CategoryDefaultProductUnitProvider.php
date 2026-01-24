<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;

/**
 * Provides default product unit precision for categories.
 *
 * Implements the default product unit provider interface to supply unit and precision settings
 * from the category hierarchy, allowing products to inherit default units from their category.
 */
class CategoryDefaultProductUnitProvider implements DefaultProductUnitProviderInterface
{
    /**
     * @var Category
     */
    protected $category;

    public function setCategory(?Category $category = null)
    {
        $this->category = $category;
    }

    /**
     * @return ProductUnitPrecision|null
     */
    #[\Override]
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
