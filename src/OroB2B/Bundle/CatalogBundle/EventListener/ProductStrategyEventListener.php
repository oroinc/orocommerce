<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

class ProductStrategyEventListener
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

    /** @var Category[] */
    protected $categories = [];

    /**
     * @inheritDoc
     */
    public function __construct(ManagerRegistry $registry, $categoryClass)
    {
        $this->registry = $registry;
        $this->categoryClass = $categoryClass;
    }

    /**
     * @param ProductStrategyEvent $event
     */
    public function onProcessAfter(ProductStrategyEvent $event)
    {
        $rawData = $event->getRawData();
        if (empty($rawData[self::CATEGORY_KEY])) {
            return;
        }

        $categoryDefaultTitle = $rawData[self::CATEGORY_KEY];
        $category = $this->getCategory($categoryDefaultTitle);
        if ($category) {
            $product = $event->getProduct();
            $category->addProduct($product);
        }
    }

    /**
     * @param string $categoryDefaultTitle
     * @return null|Category
     */
    protected function getCategory($categoryDefaultTitle)
    {
        if (array_key_exists($categoryDefaultTitle, $this->categories)) {
            return $this->categories[$categoryDefaultTitle];
        }

        $category = $this->getCategoryRepository()->findOneByDefaultTitle($categoryDefaultTitle);
        if (!$category) {
            $this->categories[$categoryDefaultTitle] = null;

            return null;
        }

        $this->categories[$categoryDefaultTitle] = $this->getEntityManager()
            ->getReference($this->categoryClass, $category->getId());

        return $this->categories[$categoryDefaultTitle];
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
}
