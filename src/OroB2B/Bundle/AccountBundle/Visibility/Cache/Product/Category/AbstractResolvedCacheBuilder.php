<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolver;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractResolvedCacheBuilder implements CategoryCaseCacheBuilderInterface
{
    /** @var Registry */
    protected $registry;

    /** @var CategoryVisibilityResolver */
    protected $categoryVisibilityResolver;

    /** @var string */
    protected $categoryVisibilityClass;

    public function __construct(Registry $registry, CategoryVisibilityResolver $categoryVisibilityResolver)
    {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }

    /**
     * @param string $categoryVisibilityClass
     */
    public function setCategoryVisibilityClass($categoryVisibilityClass)
    {
        $this->categoryVisibilityClass = $categoryVisibilityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        // TODO: Implement resolveVisibilitySettings() method.
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        // TODO: Implement buildCache() method.
    }

    /**
     * {@inheritdoc}
     */
    public function productCategoryChanged(Product $product)
    {
        // TODO: Implement productCategoryChanged() method.
    }

    /**
     * @param Category $category
     * @param $target
     * @return array
     */
    protected function getCategoryIdsForUpdate(Category $category, $target)
    {
        $categoriesWithStaticFallback = $this->getChildCategoriesWithStaticFallback($category, $target);
        $childCategories = $this->getChildCategoriesWithToParentFallback(
            $category,
            $categoriesWithStaticFallback,
            $target
        );

        $categoryIds = array_map(
            function ($category) {
                return $category['id'];
            },
            $childCategories
        );

        $categoryIds[] = $category->getId();

        return $categoryIds;
    }

    /**
     * @param Category $category
     * @param object|null $target
     * @return array
     */
    protected function getChildCategoriesWithStaticFallback(Category $category, $target)
    {
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilder($category);

        $qb = $this->joinCategoryVisibility($qb, $target);
        $qb = $this->restrictStaticFallback($qb);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param Category $category
     * @param array $categoriesWithStaticFallback
     * @param $target
     * @return array
     */
    protected function getChildCategoriesWithToParentFallback(
        Category $category,
        array $categoriesWithStaticFallback,
        $target
    ) {
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilder($category);

        $qb = $this->joinCategoryVisibility($qb, $target);
        $qb = $this->restrictToParentFallback($qb);

        foreach ($categoriesWithStaticFallback as $node) {
            $qb->andWhere(
                $qb->expr()->not(
                    $qb->expr()->andX(
                        $qb->expr()->gt('node.level', $node['level']),
                        $qb->expr()->gt('node.left', $node['left']),
                        $qb->expr()->lt('node.right', $node['right'])
                    )
                )
            );
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param bool $visibility
     * @return int
     */
    protected function convertVisibility($visibility)
    {
        return $visibility
            ? BaseProductVisibilityResolved::VISIBILITY_VISIBLE
            : BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    abstract protected function restrictStaticFallback(QueryBuilder $qb);

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    abstract protected function restrictToParentFallback(QueryBuilder $qb);

    /**
     * @param QueryBuilder $qb
     * @param object|null $target
     * @return QueryBuilder
     */
    abstract protected function joinCategoryVisibility(QueryBuilder $qb, $target);
}
