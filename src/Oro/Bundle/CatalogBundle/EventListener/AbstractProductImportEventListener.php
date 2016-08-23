<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ProductBundle\Entity\Product;

abstract class AbstractProductImportEventListener
{
    const CATEGORY_KEY = 'category.default.title';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $categoryClass;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var array */
    protected $categoriesByTitle = [];

    /** @var array */
    protected $categoriesByProduct = [];

    /**
     * @param ManagerRegistry $registry
     * @param string $categoryClass
     */
    public function __construct(ManagerRegistry $registry, $categoryClass)
    {
        $this->registry = $registry;
        $this->categoryClass = $categoryClass;
    }

    /** @return CategoryRepository */
    protected function getCategoryRepository()
    {
        if (!$this->categoryRepository) {
            $this->categoryRepository = $this->registry->getRepository($this->categoryClass);
        }

        return $this->categoryRepository;
    }

    /**
     * Clean up caches on clear UoW
     */
    public function onClear()
    {
        $this->categoriesByProduct = $this->categoriesByTitle = [];
    }

    /**
     * @param string $categoryDefaultTitle
     * @return null|Category
     */
    protected function getCategoryByDefaultTitle($categoryDefaultTitle)
    {
        if (array_key_exists($categoryDefaultTitle, $this->categoriesByTitle)) {
            return $this->categoriesByTitle[$categoryDefaultTitle];
        }

        $category = $this->getCategoryRepository()->findOneByDefaultTitle($categoryDefaultTitle);
        if (!$category) {
            $this->categoriesByTitle[$categoryDefaultTitle] = null;

            return null;
        }

        $this->categoriesByTitle[$categoryDefaultTitle] = $category;

        return $category;
    }

    /**
     * @param Product $product
     * @param bool $includeTitles
     * @return null|Category
     */
    protected function getCategoryByProduct(Product $product, $includeTitles = false)
    {
        $sku = $product->getSku();

        if (array_key_exists($sku, $this->categoriesByProduct)) {
            return $this->categoriesByProduct[$sku];
        }

        $category = $this->getCategoryRepository()->findOneByProductSku($sku, $includeTitles);
        if (!$category) {
            $this->categoriesByProduct[$sku] = null;

            return null;
        }

        $this->categoriesByProduct[$sku] = $category;

        return $category;
    }
}
