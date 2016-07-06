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
     * @var int
     */
    protected $categoryId;

    /**
     * @param int $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return ProductUnitPrecision|null
     */
    public function getDefaultProductUnitPrecision()
    {
        if (!$this->categoryId) {
            return null;
        } else {
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = $this->getRepository('OroB2BCatalogBundle:Category');
            /** @var Category $category */
            $category = $categoryRepository->findOneById($this->categoryId);
            do {
                /** @var CategoryUnitPrecision $categoryUnitPrecision */
                $categoryUnitPrecision = null;
                if ($category->getDefaultProductOptions()) {
                    $categoryUnitPrecision = $category->getDefaultProductOptions()->getUnitPrecision();
                }

                if (null != $categoryUnitPrecision && null != $categoryUnitPrecision->getUnit()) {
                    return $this->createProductUnitPrecision(
                        $categoryUnitPrecision->getUnit(),
                        $categoryUnitPrecision->getPrecision()
                    );
                }
                $category = $category->getParentCategory();
            } while (null != $category);
        }
        return null;
    }
}
