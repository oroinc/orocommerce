<?php

namespace OroB2B\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;

class FeaturedCategoriesProductsProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var FeaturedCategoriesProvider
     */
    protected $featuredCategoriesProvider;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ProductManager $productManager
     */
    protected $productManager;

    /**
     * @param FeaturedCategoriesProvider $featuredCategoriesProvider
     * @param CategoryRepository $categoryRepository
     * @param ProductManager $productManager
     */
    public function __construct(
        FeaturedCategoriesProvider $featuredCategoriesProvider,
        CategoryRepository $categoryRepository,
        ProductManager $productManager
    ) {
        $this->featuredCategoriesProvider = $featuredCategoriesProvider;
        $this->categoryRepository = $categoryRepository;
        $this->productManager = $productManager;
    }

    /**
     * @param ContextInterface $context
     * @return Category[]
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $categories = $this->featuredCategoriesProvider->getData($context);
            $this->setFeaturedCategoriesProducts($categories);
        }

        return $this->data;
    }

    public function setFeaturedCategoriesProducts($categories)
    {
        $qb = $this->categoryRepository->getCategoriesProductsCountQueryBuilder($categories);
        $this->productManager->restrictQueryBuilder($qb, []);
        $categories = $qb->getQuery()->getResult();
        $countByCategories = [];

        foreach ($categories as $category) {
            $countByCategories[$category['id']] = $category['products_count'];
        }

        $this->data = $countByCategories;
    }
}
