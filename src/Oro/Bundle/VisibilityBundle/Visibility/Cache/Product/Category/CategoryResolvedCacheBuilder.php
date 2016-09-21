<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CategoryVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\PositionChangeCategorySubtreeCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeCategorySubtreeCacheBuilder;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CategoryResolvedCacheBuilder extends AbstractResolvedCacheBuilder implements CategoryCaseCacheBuilderInterface
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

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['category' => $category];

        $repository = $this->getRepository();

        $hasCategoryVisibilityResolved = $repository->hasEntity($where);

        if (!$hasCategoryVisibilityResolved && $selectedVisibility !== CategoryVisibility::CONFIG) {
            $insert = true;
        }

        if (in_array($selectedVisibility, [CategoryVisibility::HIDDEN, CategoryVisibility::VISIBLE])) {
            $visibility = $this->convertStaticVisibility($selectedVisibility);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility === CategoryVisibility::CONFIG) {
            // fallback to config is default for account group and should be removed if exists
            if ($hasCategoryVisibilityResolved) {
                $delete = true;
            }

            $visibility = CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        } elseif ($selectedVisibility === CategoryVisibility::PARENT_CATEGORY) {
            list($visibility, $source) = $this->getParentCategoryVisibilityAndSource($category);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => $source,
            ];
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown visibility %s', $selectedVisibility));
        }

        $this->executeDbQuery($repository, $insert, $delete, $update, $where);

        $this->visibilityChangeCategorySubtreeCacheBuilder->resolveVisibilitySettings($category, $visibility);
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
        $repository = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CategoryVisibility');
        /** @var CategoryRepository $resolvedRepository */
        $resolvedRepository = $this->registry->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertFromSelectQueryExecutor);

        // resolved parent category values
        $categoryVisibilities = $this->indexVisibilities($repository->getCategoriesVisibilities(), 'category_id');
        $categoryIds = [
            CategoryVisibilityResolved::VISIBILITY_VISIBLE => [],
            CategoryVisibilityResolved::VISIBILITY_HIDDEN => [],
            CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG => [],
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
            $resolvedVisibility = CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        // save resolved visibility to use it in following iterations
        $categoryVisibilities[$categoryId]['resolved_visibility'] = $resolvedVisibility;

        return $resolvedVisibility;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved');
    }

    /**
     * @return CategoryRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved');
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getParentCategoryVisibilityAndSource(Category $category)
    {
        $parentCategory = $category->getParentCategory();
        if ($parentCategory) {
            return [
                $this->getRepository()->getFallbackToAllVisibility($parentCategory),
                CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            ];
        } else {
            return [
                CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                CategoryVisibilityResolved::SOURCE_STATIC
            ];
        }
    }
}
