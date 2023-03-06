<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\PositionChangeCategorySubtreeCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeCategorySubtreeCacheBuilder;

/**
 * The category visibility cache builder.
 */
class CategoryResolvedCacheBuilder extends AbstractCategoryResolvedCacheBuilder implements
    CategoryCaseCacheBuilderInterface
{
    private ScopeManager $scopeManager;
    private InsertFromSelectQueryExecutor $insertExecutor;
    private VisibilityChangeCategorySubtreeCacheBuilder $visibilityChangeCategorySubtreeCacheBuilder;
    private PositionChangeCategorySubtreeCacheBuilder $positionChangeCategorySubtreeCacheBuilder;

    public function __construct(
        ManagerRegistry $doctrine,
        ProductIndexScheduler $indexScheduler,
        ScopeManager $scopeManager,
        InsertFromSelectQueryExecutor $insertExecutor,
        VisibilityChangeCategorySubtreeCacheBuilder $visibilityChangeCategorySubtreeCacheBuilder,
        PositionChangeCategorySubtreeCacheBuilder $positionChangeCategorySubtreeCacheBuilder
    ) {
        parent::__construct($doctrine, $indexScheduler);
        $this->scopeManager = $scopeManager;
        $this->insertExecutor = $insertExecutor;
        $this->visibilityChangeCategorySubtreeCacheBuilder = $visibilityChangeCategorySubtreeCacheBuilder;
        $this->positionChangeCategorySubtreeCacheBuilder = $positionChangeCategorySubtreeCacheBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        /** @var CategoryVisibility $visibilitySettings */
        $category = $visibilitySettings->getCategory();
        $scope = $visibilitySettings->getScope();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['category' => $category];

        $repository = $this->getCategoryRepository();

        $hasCategoryVisibilityResolved = $repository->hasEntity($where);

        if (!$hasCategoryVisibilityResolved && $selectedVisibility !== CategoryVisibility::CONFIG) {
            $insert = true;
        }

        if (\in_array($selectedVisibility, [CategoryVisibility::HIDDEN, CategoryVisibility::VISIBLE], true)) {
            $visibility = $this->convertStaticVisibility($selectedVisibility);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
                'scope' => $scope,
            ];
        } elseif ($selectedVisibility === CategoryVisibility::CONFIG) {
            // fallback to config is default for customer group and should be removed if exists
            if ($hasCategoryVisibilityResolved) {
                $delete = true;
            }

            $visibility = CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        } elseif ($selectedVisibility === CategoryVisibility::PARENT_CATEGORY) {
            [$visibility, $source] = $this->getParentCategoryVisibilityAndSource($category);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => $source,
                'scope' => $scope,
            ];
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown visibility %s', $selectedVisibility));
        }

        $this->executeDbQuery($repository, $insert, $delete, $update, $where);

        $categories = $this->visibilityChangeCategorySubtreeCacheBuilder
            ->resolveVisibilitySettings($category, $visibility);
        $this->triggerCategoriesReindexation($categories);
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
        $categories = $this->positionChangeCategorySubtreeCacheBuilder->categoryPositionChanged($category);
        $this->triggerCategoriesReindexation($categories);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Scope $scope = null)
    {
        $resolvedRepository = $this->getCategoryRepository();

        // clear table
        $resolvedRepository->clearTable();

        if (!$scope) {
            $scope = $this->scopeManager->findOrCreate(CategoryVisibility::VISIBILITY_TYPE);
        }

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertExecutor, $scope);

        // resolved parent category values
        $categoryVisibilities = $this->indexVisibilities(
            $this->doctrine->getRepository(CategoryVisibility::class)->getCategoriesVisibilities(),
            'category_id'
        );
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
            $resolvedRepository->insertParentCategoryValues(
                $this->insertExecutor,
                $ids,
                $visibility,
                $scope
            );
        }
    }

    private function resolveVisibility(array &$categoryVisibilities, array $currentCategory): int
    {
        // visibility already resolved
        if (\array_key_exists('resolved_visibility', $currentCategory)) {
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

    private function getParentCategoryVisibilityAndSource(Category $category): array
    {
        $parentCategory = $category->getParentCategory();
        if ($parentCategory) {
            return [
                $this->getCategoryRepository()->getFallbackToAllVisibility($parentCategory),
                CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            ];
        }

        return [
            CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            CategoryVisibilityResolved::SOURCE_STATIC
        ];
    }

    private function getCategoryRepository(): CategoryRepository
    {
        return $this->doctrine->getRepository(CategoryVisibilityResolved::class);
    }
}
