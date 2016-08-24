<?php

namespace OroB2B\Bundle\CatalogBundle\Layout\DataProvider;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;

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

    public function getCountByCategories($categories)
    {
        $qb = $this->categoryRepository->getCategoriesProductsCountQueryBuilder($categories);
        $this->productManager->restrictQueryBuilder($qb, []);
        $categories = $qb->getQuery()->getResult();
        $countByCategories = [];

        foreach ($categories as $category) {
            $countByCategories[$category['id']] = $category['products_count'];
        }

        return $countByCategories;
    }
}
