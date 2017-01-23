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

        $qb = $this->categoryRepository->getCategoriesProductsCountQueryBuilder($categoriesIds);
        $this->productManager->restrictQueryBuilder($qb, []);
        $result = $qb->getQuery()->getResult();

        /** @var Category[] $categories */
        $categories = $this->categoryRepository->findBy(['id' => $categoriesIds]);
        foreach ($categories as $category) {
            $childrenIds = $this->categoryRepository->getChildrenIds($category);

            $count = 0;
            foreach ($result as $data) {
                if (in_array($data['id'], $childrenIds) || $data['id'] === $category->getId()) {
                    $count += (int) $data['products_count'];
                }
            }

            if ($count > 0) {
                $countByCategories[$category->getId()] = $count;
            }
        }

        return $countByCategories;
    }
}
