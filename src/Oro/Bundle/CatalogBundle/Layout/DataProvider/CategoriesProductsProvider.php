<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;


use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;

class CategoriesProductsProvider
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var ProductRepository
     */
    protected $searchRepository;

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
     * @param ProductRepository  $searchRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $searchRepository
    ) {
        $this->searchRepository = $searchRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param array $categoriesIds
     *
     * @return array
     */
    public function getCountByCategories($categoriesIds)
    {
        $categoriesIds[] = 33;

        /** @var Category[] $categories */
        $categories = $this->categoryRepository->findBy(['id' => $categoriesIds]);

        return $this->searchRepository->getCategoriesCounts($categories);
    }
}
