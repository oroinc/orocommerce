<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolver;
use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityResolvedRepository;

class ProductResolvedCacheBuilder
{
    const VISIBLE = 'visible';
    const HIDDEN = 'hidden';
    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectExecutor;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var string
     */
    protected $cacheClass;

    /**
     * @var CategoryVisibilityResolver
     */
    protected $categoryVisibilityResolver;

    /**
     * ProductResolvedCacheBuilder constructor.
     * @param RegistryInterface $doctrine
     * @param InsertFromSelectQueryExecutor $executor
     * @param CategoryVisibilityResolver $categoryVisibilityResolver
     * @param string $cacheClass
     */
    public function __construct(
        RegistryInterface $doctrine,
        InsertFromSelectQueryExecutor $executor,
        CategoryVisibilityResolver $categoryVisibilityResolver,
        $cacheClass
    ) {
        $this->doctrine = $doctrine;
        $this->insertFromSelectExecutor = $executor;
        $this->cacheClass = $cacheClass;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }


    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings($visibilitySettings)
    {
        // TODO: Implement resolveVisibilitySettings() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported($visibilitySettings)
    {
        return $visibilitySettings instanceof ProductVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function updateResolvedVisibilityByCategory(Category $category)
    {
        // TODO: Implement updateResolvedVisibilityByCategory() method.
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductResolvedVisibility(Product $product)
    {
        // TODO: Implement updateProductResolvedVisibility() method.
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        //todo transaction
        $this->getRepository()->clearTable();

        $categoriesGrouped = $this->getCategories();
        $this->getRepository()->insertByCategory(
            $this->insertFromSelectExecutor,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            $categoriesGrouped[self::VISIBLE]
        );
        $this->getRepository()->insertByCategory(
            $this->insertFromSelectExecutor,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            $categoriesGrouped[self::HIDDEN]
        );

        $this->getRepository()->deleteByVisibility(ProductVisibility::CONFIG);
        $this->getRepository()->updateFromBaseTable(
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            ProductVisibility::VISIBLE
        );
        $this->getRepository()->updateFromBaseTable(
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            ProductVisibility::HIDDEN
        );
    }

    /**
     * @return array
     */
    protected function getCategories()
    {
        // temporary
        /** @var Category[] $categories */
        $categories = $this->doctrine->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select('partial category.{id}')
            ->getQuery()
            ->getResult();

        $categoriesGrouped = [self::VISIBLE => [], self::HIDDEN => []];

        foreach ($categories as $category) {
            if ($this->categoryVisibilityResolver->isCategoryVisible($category)) {
                $categoriesGrouped[self::VISIBLE][] = $category->getId();
            } else {
                $categoriesGrouped[self::HIDDEN][] = $category->getId();
            }
        }

        return $categoriesGrouped;
    }

    /**
     * @return ProductVisibilityResolvedRepository
     */
    protected function getRepository()
    {
        return $this->doctrine
            ->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);
    }
}
