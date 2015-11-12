<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;

abstract class AbstractProductImportEventListener
{
    const CATEGORY_KEY = 'category.default.title';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $categoryClass;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var EntityManager */
    protected $categoryManager;

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

    /** @return EntityManager */
    protected function getEntityManager()
    {
        if (!$this->categoryManager) {
            $this->categoryManager = $this->registry->getManagerForClass($this->categoryClass);
        }

        return $this->categoryManager;
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

        $this->categoriesByTitle[$categoryDefaultTitle] = $this->getEntityManager()
            ->getReference($this->categoryClass, $category->getId());

        return $this->categoriesByTitle[$categoryDefaultTitle];
    }

    /**
     * @param Product $product
     * @return null|Category
     */
    protected function getCategoryByProduct(Product $product)
    {
        $sku = $product->getSku();

        if (array_key_exists($sku, $this->categoriesByProduct)) {
            return $this->categoriesByProduct[$sku];
        }

        $category = $this->getCategoryRepository()->findOneByProductSku($sku);
        if (!$category) {
            $this->categoriesByProduct[$sku] = null;

            return null;
        }

        $this->categoriesByProduct[$sku] = $this->getEntityManager()
            ->getReference($this->categoryClass, $category->getId());

        return $this->categoriesByProduct[$sku];
    }
}
