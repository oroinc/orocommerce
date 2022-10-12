<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupCategoryRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;

/**
 * The customer group category visibility cache builder.
 */
class CustomerGroupCategoryResolvedCacheBuilder extends AbstractCategoryResolvedCacheBuilder
{
    private InsertFromSelectQueryExecutor $insertExecutor;
    private VisibilityChangeGroupSubtreeCacheBuilder $visibilityChangeCustomerGroupSubtreeCacheBuilder;

    public function __construct(
        ManagerRegistry $doctrine,
        ProductIndexScheduler $indexScheduler,
        InsertFromSelectQueryExecutor $insertExecutor,
        VisibilityChangeGroupSubtreeCacheBuilder $visibilityChangeCustomerGroupSubtreeCacheBuilder
    ) {
        parent::__construct($doctrine, $indexScheduler);
        $this->insertExecutor = $insertExecutor;
        $this->visibilityChangeCustomerGroupSubtreeCacheBuilder = $visibilityChangeCustomerGroupSubtreeCacheBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        /** @var CustomerGroupCategoryVisibility $visibilitySettings */
        $category = $visibilitySettings->getCategory();
        $scope = $visibilitySettings->getScope();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'category' => $category];

        $repository = $this->getCustomerGroupCategoryRepository();

        $hasCustomerGroupCategoryVisibilityResolved = $repository->hasEntity($where);

        if (!$hasCustomerGroupCategoryVisibilityResolved
            && $selectedVisibility !== CustomerGroupCategoryVisibility::CATEGORY
        ) {
            $insert = true;
        }

        if (\in_array(
            $selectedVisibility,
            [CustomerGroupCategoryVisibility::HIDDEN, CustomerGroupCategoryVisibility::VISIBLE],
            true
        )) {
            $visibility = $this->convertStaticVisibility($selectedVisibility);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility === CustomerGroupCategoryVisibility::CATEGORY) {
            // fallback to category is default for customer group and should be removed if exists
            if ($hasCustomerGroupCategoryVisibilityResolved) {
                $delete = true;
            }

            $visibility = $this->getCategoryRepository()->getFallbackToAllVisibility($category);
        } elseif ($selectedVisibility === CustomerGroupCategoryVisibility::PARENT_CATEGORY) {
            [$visibility, $source] = $this->getParentCategoryVisibilityAndSource($category, $scope);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => $source,
            ];
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown visibility %s', $selectedVisibility));
        }
        $this->executeDbQuery($repository, $insert, $delete, $update, $where);

        $categories = $this->visibilityChangeCustomerGroupSubtreeCacheBuilder
            ->resolveVisibilitySettings($category, $scope, $visibility);
        $this->triggerCategoriesReindexation($categories);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof CustomerGroupCategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Scope $scope = null)
    {
        $resolvedRepository = $this->getCustomerGroupCategoryRepository();

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertExecutor);

        // resolve parent category values
        $groupVisibilities = $this->indexVisibilities(
            $resolvedRepository->getParentCategoryVisibilities(),
            'visibility_id'
        );
        $groupVisibilityIds = [
            CustomerGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE => [],
            CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN => [],
            CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG => [],
        ];
        foreach ($groupVisibilities as $visibilityId => $groupVisibility) {
            $resolvedVisibility = $this->resolveVisibility($groupVisibilities, $groupVisibility);
            $groupVisibilityIds[$resolvedVisibility][] = $visibilityId;
        }
        foreach ($groupVisibilityIds as $visibility => $ids) {
            $resolvedRepository->insertParentCategoryValues($this->insertExecutor, $ids, $visibility);
        }
    }

    private function getParentCategoryVisibilityAndSource(Category $category, Scope $scope): array
    {
        $parentCategory = $category->getParentCategory();
        if ($parentCategory) {
            return [
                $this->getCustomerGroupCategoryRepository()->getFallbackToGroupVisibility($parentCategory, $scope),
                CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            ];
        }

        return [
            CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC
        ];
    }

    private function resolveVisibility(array &$groupVisibilities, array $currentGroup): int
    {
        // visibility already resolved
        if (\array_key_exists('resolved_visibility', $currentGroup)) {
            return $currentGroup['resolved_visibility'];
        }

        $visibilityId = $currentGroup['visibility_id'];
        $parentVisibility = $currentGroup['parent_visibility'];
        $parentVisibilityId = $currentGroup['parent_visibility_id'];
        $parentCategoryVisibilityResolved = $currentGroup['parent_category_resolved_visibility'];

        $resolvedVisibility = null;

        // category fallback (visibility to all)
        if (null === $parentVisibility) {
            $resolvedVisibility = $parentCategoryVisibilityResolved;
        // parent category fallback
        } elseif ($parentVisibility === CustomerGroupCategoryVisibility::PARENT_CATEGORY) {
            $parentGroup = $groupVisibilities[$parentVisibilityId];
            $resolvedVisibility = $this->resolveVisibility($groupVisibilities, $parentGroup);
        // static visibility
        } else {
            $resolvedVisibility
                = $this->convertVisibility($parentVisibility === CustomerGroupCategoryVisibility::VISIBLE);
        }

        // config value (default)
        if (null === $resolvedVisibility) {
            $resolvedVisibility = CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        $groupVisibilities[$visibilityId]['resolved_visibility'] = $resolvedVisibility;

        return $resolvedVisibility;
    }

    public function getCustomerGroupCategoryRepository(): CustomerGroupCategoryRepository
    {
        return $this->doctrine->getRepository(CustomerGroupCategoryVisibilityResolved::class);
    }

    private function getCategoryRepository(): CategoryRepository
    {
        return $this->doctrine->getRepository(CategoryVisibilityResolved::class);
    }
}
