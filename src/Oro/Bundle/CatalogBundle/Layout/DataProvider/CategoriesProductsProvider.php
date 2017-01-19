<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;

class CategoriesProductsProvider
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ProductManager $productManager
     */
    protected $productManager;

    /**
     * @param CategoryRepository $categoryRepository
     * @param ProductManager $productManager
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductManager $productManager
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productManager = $productManager;
    }

    /**
     * @param array $categoriesIds
     *
     * @return array
     */
    public function getCountByCategories($categoriesIds)
    {
        $countByCategories = [];

        foreach ($categoriesIds as $categoryId) {
            $countByCategories[$categoryId] = 0;

            $category = $this->categoryRepository->find($categoryId);
            $categoriesWithChild =
                $this->categoryRepository->getChildrenWithTitles($category, false, 'left', 'ASC', true);

            $qb = $this->categoryRepository->getCategoriesProductsCountQueryBuilder($categoriesWithChild);
            $this->productManager->restrictQueryBuilder($qb, []);
            $categoriesProducts = $qb->getQuery()->getResult();

            foreach ($categoriesProducts as $categoriesProduct) {
                $countByCategories[$categoryId] += $categoriesProduct['products_count'];
            }
        }

        return $countByCategories;
    }
}
