<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Search\ProductRepository;

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
        $countByCategories = [];

        /** @var Category[] $categories */
        $categories = $this->categoryRepository->findBy(['id' => $categoriesIds]);
        $categoriesCounts = [];

        foreach ($categories as $category) {
            $categoryId = $category->getId();
            $searchQb = $this->searchRepository->createQuery();
            $searchQb->addSelect('id')
                ->addWhere(Criteria::expr()->eq('integer.category_id', $categoryId));

            $count = $searchQb->getTotalCount();

            $categoriesCounts[$categoryId] = $count;
            unset($searchQb);
        }

        foreach ($categories as $category) {
            $childrenIds = $this->categoryRepository->getChildrenIds($category);

            $count = $categoriesCounts[$category->getId()] ?? 0;

            foreach ($childrenIds as $id) {
                if (isset($categoriesCounts[$id])) {
                    $count += (int) $categoriesCounts[$id];
                }
            }

            if ($count > 0) {
                $countByCategories[$category->getId()] = $count;
            }
        }

        return $countByCategories;
    }
}
