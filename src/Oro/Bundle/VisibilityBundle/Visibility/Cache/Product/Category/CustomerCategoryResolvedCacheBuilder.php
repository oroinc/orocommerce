<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerCategoryRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeCustomerSubtreeCacheBuilder;

/**
 * The customer category visibility cache builder.
 */
class CustomerCategoryResolvedCacheBuilder extends AbstractCategoryResolvedCacheBuilder
{
    private ScopeManager $scopeManager;
    private InsertFromSelectQueryExecutor $insertExecutor;
    private VisibilityChangeCustomerSubtreeCacheBuilder $visibilityChangeCustomerSubtreeCacheBuilder;

    public function __construct(
        ManagerRegistry $doctrine,
        ProductIndexScheduler $indexScheduler,
        ScopeManager $scopeManager,
        InsertFromSelectQueryExecutor $insertExecutor,
        VisibilityChangeCustomerSubtreeCacheBuilder $visibilityChangeCustomerSubtreeCacheBuilder
    ) {
        parent::__construct($doctrine, $indexScheduler);
        $this->scopeManager = $scopeManager;
        $this->insertExecutor = $insertExecutor;
        $this->visibilityChangeCustomerSubtreeCacheBuilder = $visibilityChangeCustomerSubtreeCacheBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        /** @var CustomerCategoryVisibility $visibilitySettings */
        $category = $visibilitySettings->getCategory();
        $scope = $visibilitySettings->getScope();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'category' => $category];

        $repository = $this->getCustomerCategoryRepository();

        $hasCustomerCategoryVisibilityResolved = $repository->hasEntity($where);

        if (!$hasCustomerCategoryVisibilityResolved
            && $selectedVisibility !== CustomerCategoryVisibility::CUSTOMER_GROUP
        ) {
            $insert = true;
        }

        if (\in_array(
            $selectedVisibility,
            [CustomerCategoryVisibility::HIDDEN, CustomerCategoryVisibility::VISIBLE],
            true
        )) {
            $visibility = $this->convertStaticVisibility($selectedVisibility);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility === CustomerCategoryVisibility::CATEGORY) {
            $visibility = $this->doctrine->getRepository(CategoryVisibilityResolved::class)
                ->getFallbackToAllVisibility($category);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility === CustomerCategoryVisibility::CUSTOMER_GROUP) {
            // Fallback to customer group is default for customer and should be removed if exists
            if ($hasCustomerCategoryVisibilityResolved) {
                $delete = true;
            }

            if ($scope->getCustomer()->getGroup()) {
                $visibility = $this->doctrine->getRepository(CustomerGroupCategoryVisibilityResolved::class)
                    ->getFallbackToGroupVisibility($category, $scope);
            } else {
                $visibility = $this->doctrine->getRepository(CategoryVisibilityResolved::class)
                    ->getFallbackToAllVisibility($category);
            }
        } elseif ($selectedVisibility === CustomerCategoryVisibility::PARENT_CATEGORY) {
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

        $categories = $this->visibilityChangeCustomerSubtreeCacheBuilder->resolveVisibilitySettings(
            $category,
            $scope,
            $visibility
        );
        $this->triggerCategoriesReindexation($categories);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof CustomerCategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Scope $scope = null)
    {
        $resolvedRepository = $this->getCustomerCategoryRepository();

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertExecutor);

        // resolve values with fallback to category (visibility to all)
        $resolvedRepository->insertCategoryValues($this->insertExecutor);

        // resolve parent category values
        $customerVisibilities = $this->indexVisibilities(
            $resolvedRepository->getParentCategoryVisibilities(),
            'visibility_id'
        );
        $customerVisibilityIds = [
            CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE => [],
            CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN => [],
            CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG => [],
        ];
        foreach ($customerVisibilities as $visibilityId => $groupVisibility) {
            $resolvedVisibility = $this->resolveVisibility($customerVisibilities, $groupVisibility);
            $customerVisibilityIds[$resolvedVisibility][] = $visibilityId;
        }
        foreach ($customerVisibilityIds as $visibility => $ids) {
            $resolvedRepository->insertParentCategoryValues($this->insertExecutor, $ids, $visibility);
        }
    }

    private function resolveVisibility(array &$customerVisibilities, array $currentGroup): int
    {
        // visibility already resolved
        if (\array_key_exists('resolved_visibility', $currentGroup)) {
            return $currentGroup['resolved_visibility'];
        }

        $visibilityId = $currentGroup['visibility_id'];
        $parentVisibility = $currentGroup['parent_visibility'];
        $parentVisibilityId = $currentGroup['parent_visibility_id'];
        $parentGroupVisibilityResolved = $currentGroup['parent_group_resolved_visibility'];
        $parentCategoryVisibilityResolved = $currentGroup['parent_category_resolved_visibility'];

        // customer group fallback
        if (null === $parentVisibility) {
            // use group visibility if defined, otherwise use category visibility
            $resolvedVisibility = $parentGroupVisibilityResolved ?? $parentCategoryVisibilityResolved;
        // category fallback (visibility to all)
        } elseif ($parentVisibility === CustomerCategoryVisibility::CATEGORY) {
            $resolvedVisibility = $parentCategoryVisibilityResolved;
        // parent category fallback
        } elseif ($parentVisibility === CustomerCategoryVisibility::PARENT_CATEGORY) {
            $parentGroup = $customerVisibilities[$parentVisibilityId];
            $resolvedVisibility = $this->resolveVisibility($customerVisibilities, $parentGroup);
        // static visibility
        } else {
            $resolvedVisibility = $this->convertVisibility($parentVisibility === CustomerCategoryVisibility::VISIBLE);
        }

        // config value (default)
        if (null === $resolvedVisibility) {
            $resolvedVisibility = CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        $customerVisibilities[$visibilityId]['resolved_visibility'] = $resolvedVisibility;

        return $resolvedVisibility;
    }

    private function getParentCategoryVisibilityAndSource(Category $category, Scope $scope): array
    {
        $parentCategory = $category->getParentCategory();
        if ($parentCategory) {
            $groupScope = null;
            if ($scope->getCustomer()->getGroup()) {
                $groupScope = $this->scopeManager->find(
                    'customer_category_visibility',
                    ['customerGroup' => $scope->getCustomer()->getGroup()]
                );
            }

            return [
                $this->getCustomerCategoryRepository()->getFallbackToCustomerVisibility(
                    $parentCategory,
                    $scope,
                    $groupScope
                ),
                CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            ];
        }

        return [
            CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            CustomerCategoryVisibilityResolved::SOURCE_STATIC
        ];
    }

    private function getCustomerCategoryRepository(): CustomerCategoryRepository
    {
        return $this->doctrine->getRepository(CustomerCategoryVisibilityResolved::class);
    }
}
