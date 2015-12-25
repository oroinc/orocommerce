<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\CategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\PositionChangeCategorySubtreeCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeCategorySubtreeCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;

class ProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder implements CategoryCaseCacheBuilderInterface
{
    /** @var VisibilityChangeCategorySubtreeCacheBuilder */
    protected $visibilityChangeCategorySubtreeCacheBuilder;

    /** @var PositionChangeCategorySubtreeCacheBuilder */
    protected $positionChangeCategorySubtreeCacheBuilder;

    /**
     * @param VisibilityChangeCategorySubtreeCacheBuilder $visibilityChangeCategorySubtreeCacheBuilder
     */
    public function setVisibilityChangeCategorySubtreeCacheBuilder(
        VisibilityChangeCategorySubtreeCacheBuilder $visibilityChangeCategorySubtreeCacheBuilder
    ) {
        $this->visibilityChangeCategorySubtreeCacheBuilder = $visibilityChangeCategorySubtreeCacheBuilder;
    }

    /**
     * @param PositionChangeCategorySubtreeCacheBuilder $positionChangeCategorySubtreeCacheBuilder
     */
    public function setPositionChangeCategorySubtreeCacheBuilder(
        PositionChangeCategorySubtreeCacheBuilder $positionChangeCategorySubtreeCacheBuilder
    ) {
        $this->positionChangeCategorySubtreeCacheBuilder = $positionChangeCategorySubtreeCacheBuilder;
    }

    /**
     * @param VisibilityInterface|CategoryVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $category = $visibilitySettings->getCategory();

        $this->visibilityChangeCategorySubtreeCacheBuilder->resolveVisibilitySettings($category);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof CategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function categoryPositionChanged(Category $category)
    {
        $this->positionChangeCategorySubtreeCacheBuilder->categoryPositionChanged($category);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        /** @var CategoryVisibilityRepository $repository */
        $repository = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\CategoryVisibility');
        /** @var CategoryRepository $resolvedRepository */
        $resolvedRepository = $this->registry->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertFromSelectQueryExecutor);

        // resolved parent category values
        $categoryVisibilities = $this->indexVisibilities($repository->getCategoriesVisibilities());
        $categoryIds = [
            CategoryVisibilityResolved::VISIBILITY_VISIBLE => [],
            CategoryVisibilityResolved::VISIBILITY_HIDDEN => [],
        ];
        foreach ($categoryVisibilities as $categoryId => $currentCategory) {
            // if fallback to parent category
            if (null === $currentCategory['visibility']) {
                $resolvedVisibility = $this->resolveVisibility($categoryVisibilities, $currentCategory);
                $categoryIds[$resolvedVisibility][] = $categoryId;
            }
        }
        foreach ($categoryIds as $visibility => $ids) {
            $resolvedRepository->insertParentCategoryValues($this->insertFromSelectQueryExecutor, $ids, $visibility);
        }
    }

    /**
     * @param array $categoryVisibilities
     * @param array $currentCategory
     * @return int
     */
    protected function resolveVisibility(array &$categoryVisibilities, array $currentCategory)
    {
        // visibility already resolved
        if (array_key_exists('resolved_visibility', $currentCategory)) {
            return $currentCategory['resolved_visibility'];
        }

        $categoryId = $currentCategory['category_id'];
        $parentCategoryId = $currentCategory['category_parent_id'];
        $visibility = $currentCategory['visibility'];

        $resolvedVisibility = null;

        // fallback to parent category
        if (null === $visibility) {
            if ($parentCategoryId && !empty($categoryVisibilities[$parentCategoryId])) {
                $resolvedVisibility = $this->resolveVisibility(
                    $categoryVisibilities,
                    $categoryVisibilities[$parentCategoryId]
                );
            }

        // static value
        } elseif ($visibility !== CategoryVisibility::CONFIG) {
            $resolvedVisibility = $this->convertVisibility($visibility === CategoryVisibility::VISIBLE);
        }

        // config value (default)
        if (null === $resolvedVisibility) {
            $resolvedVisibility = $this->getVisibilityFromConfig();
        }

        // save resolved visibility to use it in following iterations
        $categoryVisibilities[$categoryId]['resolved_visibility'] = $resolvedVisibility;

        return $resolvedVisibility;
    }
}
