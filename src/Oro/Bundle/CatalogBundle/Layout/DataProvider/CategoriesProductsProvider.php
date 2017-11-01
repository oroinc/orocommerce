<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;

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
        $categories = $this->categoryRepository->findBy(['id' => $categoriesIds]);

        return $this->searchRepository->getCategoriesCounts($categories);
    }
}
