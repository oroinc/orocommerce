<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Criteria;

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
        /** @var Category[] $categories */
        $categories = $this->categoryRepository->findBy(['id' => $categoriesIds]);
        $categoriesCounts = [];

        $searchQb = $this->searchRepository->createQuery();
        $searchQb->addSelect('id')
            ->setFrom('oro_product_WEBSITE_ID')
            ->addWhere(Criteria::expr()->in('integer.category_id', $categoriesIds));

        $counts = $this->searchRepository->getCategoryCounts($searchQb);

        foreach ($categories as $category) {
            $categoryId = $category->getId();
            $categoryPath = $category->getMaterializedPath();
            $categoriesCounts[$categoryId] = isset($counts[$categoryPath]) ? (int) $counts[$categoryPath] : 0;

            if (!($childrenIds = $this->categoryRepository->getChildrenIds($category))) {
                continue;
            }

            unset($searchQb);
            $searchQb = $this->searchRepository->createQuery();
            $searchQb->addSelect('id')
                ->setFrom('oro_product_WEBSITE_ID')
                ->addWhere(Criteria::expr()->in('integer.category_id', $childrenIds));

            if (!($childrenCounts = $this->searchRepository->getCategoryCounts($searchQb))) {
                continue;
            }

            foreach ($childrenCounts as $count) {
                $categoriesCounts[$categoryId] += $count;
            }
        }

        return $categoriesCounts;
    }
}
