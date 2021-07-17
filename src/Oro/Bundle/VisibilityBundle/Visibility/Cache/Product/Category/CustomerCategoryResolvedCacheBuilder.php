<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerCategoryRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeCustomerSubtreeCacheBuilder;

class CustomerCategoryResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var VisibilityChangeCustomerSubtreeCacheBuilder */
    protected $visibilityChangeCustomerSubtreeCacheBuilder;

    public function setVisibilityChangeCustomerSubtreeCacheBuilder(
        VisibilityChangeCustomerSubtreeCacheBuilder $visibilityChangeCustomerSubtreeCacheBuilder
    ) {
        $this->visibilityChangeCustomerSubtreeCacheBuilder = $visibilityChangeCustomerSubtreeCacheBuilder;
    }

    /**
     * @param VisibilityInterface|CustomerCategoryVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $category = $visibilitySettings->getCategory();
        $scope = $visibilitySettings->getScope();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'category' => $category];

        $repository = $this->getRepository();

        $hasCustomerCategoryVisibilityResolved = $repository->hasEntity($where);

        if (!$hasCustomerCategoryVisibilityResolved
            && $selectedVisibility !== CustomerCategoryVisibility::CUSTOMER_GROUP
        ) {
            $insert = true;
        }

        if (in_array($selectedVisibility, [CustomerCategoryVisibility::HIDDEN, CustomerCategoryVisibility::VISIBLE])) {
            $visibility = $this->convertStaticVisibility($selectedVisibility);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility === CustomerCategoryVisibility::CATEGORY) {
            $visibility = $this->registry
                ->getManagerForClass(CategoryVisibilityResolved::class)
                ->getRepository(CategoryVisibilityResolved::class)
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
                $visibility = $this->registry
                    ->getManagerForClass(CustomerGroupCategoryVisibilityResolved::class)
                    ->getRepository(CustomerGroupCategoryVisibilityResolved::class)
                    ->getFallbackToGroupVisibility($category, $scope);
            } else {
                $visibility = $this->registry
                    ->getManagerForClass(CategoryVisibilityResolved::class)
                    ->getRepository(CategoryVisibilityResolved::class)
                    ->getFallbackToAllVisibility($category);
            }
        } elseif ($selectedVisibility === CustomerCategoryVisibility::PARENT_CATEGORY) {
            list($visibility, $source) = $this->getParentCategoryVisibilityAndSource($category, $scope);
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
        /** @var CustomerCategoryRepository $resolvedRepository */
        $resolvedRepository = $this->getRepository();

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

    /**
     * @param array $customerVisibilities
     * @param array $currentGroup
     * @return int
     */
    protected function resolveVisibility(array &$customerVisibilities, array $currentGroup)
    {
        // visibility already resolved
        if (array_key_exists('resolved_visibility', $currentGroup)) {
            return $currentGroup['resolved_visibility'];
        }

        $visibilityId = $currentGroup['visibility_id'];
        $parentVisibility = $currentGroup['parent_visibility'];
        $parentVisibilityId = $currentGroup['parent_visibility_id'];
        $parentGroupVisibilityResolved = $currentGroup['parent_group_resolved_visibility'];
        $parentCategoryVisibilityResolved = $currentGroup['parent_category_resolved_visibility'];

        $resolvedVisibility = null;

        // customer group fallback
        if (null === $parentVisibility) {
            // use group visibility if defined, otherwise use category visibility
            $resolvedVisibility = $parentGroupVisibilityResolved !== null
                ? $parentGroupVisibilityResolved
                : $parentCategoryVisibilityResolved;
        // category fallback (visibility to all)
        } elseif ($parentVisibility === CustomerCategoryVisibility::CATEGORY) {
            $resolvedVisibility = $parentCategoryVisibilityResolved;
        // parent category fallback
        } elseif ($parentVisibility === CustomerCategoryVisibility::PARENT_CATEGORY) {
            $parentGroup = $customerVisibilities[$parentVisibilityId];
            $resolvedVisibility = $this->resolveVisibility($customerVisibilities, $parentGroup);
        // static visibility
        } else {
            $resolvedVisibility
                = $this->convertVisibility($parentVisibility === CustomerCategoryVisibility::VISIBLE);
        }

        // config value (default)
        if (null === $resolvedVisibility) {
            $resolvedVisibility = CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        $customerVisibilities[$visibilityId]['resolved_visibility'] = $resolvedVisibility;

        return $resolvedVisibility;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved');
    }

    /**
     * @return CustomerCategoryRepository
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param Category $category
     * @param Scope $scope
     * @return array
     */
    protected function getParentCategoryVisibilityAndSource(Category $category, Scope $scope)
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
                $this->getRepository()->getFallbackToCustomerVisibility($parentCategory, $scope, $groupScope),
                CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            ];
        } else {
            return [
                CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                CustomerCategoryVisibilityResolved::SOURCE_STATIC
            ];
        }
    }
}
