<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupCategoryRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;

class CustomerGroupCategoryResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var VisibilityChangeGroupSubtreeCacheBuilder */
    protected $visibilityChangeCustomerGroupSubtreeCacheBuilder;

    /**
     * @var EntityRepository
     */
    protected $customerGroupCategoryVisibilityRepository;

    public function setVisibilityChangeCustomerSubtreeCacheBuilder(
        VisibilityChangeGroupSubtreeCacheBuilder $visibilityChangeCustomerGroupSubtreeCacheBuilder
    ) {
        $this->visibilityChangeCustomerGroupSubtreeCacheBuilder = $visibilityChangeCustomerGroupSubtreeCacheBuilder;
    }

    /**
     * @param VisibilityInterface|CustomerGroupCategoryVisibility $visibilitySettings
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

        $repository = $this->getCustomerGroupCategoryVisibilityRepository();

        $hasCustomerGroupCategoryVisibilityResolved = $repository->hasEntity($where);

        if (!$hasCustomerGroupCategoryVisibilityResolved
            && $selectedVisibility !== CustomerGroupCategoryVisibility::CATEGORY
        ) {
            $insert = true;
        }

        if (in_array(
            $selectedVisibility,
            [
                CustomerGroupCategoryVisibility::HIDDEN,
                CustomerGroupCategoryVisibility::VISIBLE
            ]
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

            $visibility = $this->getRepository()->getFallbackToAllVisibility($category);
        } elseif ($selectedVisibility === CustomerGroupCategoryVisibility::PARENT_CATEGORY) {
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

        $categories = $this->visibilityChangeCustomerGroupSubtreeCacheBuilder
            ->resolveVisibilitySettings($category, $scope, $visibility);
        $this->triggerCategoriesReindexation($categories);
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
            return [
                $this->getCustomerGroupCategoryVisibilityRepository()
                    ->getFallbackToGroupVisibility($parentCategory, $scope),
                CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            ];
        } else {
            return [
                CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC
            ];
        }
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
        $resolvedRepository = $this->getCustomerGroupCategoryVisibilityRepository();

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

    /**
     * @param array $groupVisibilities
     * @param array $currentGroup
     * @return int
     */
    protected function resolveVisibility(array &$groupVisibilities, array $currentGroup)
    {
        // visibility already resolved
        if (array_key_exists('resolved_visibility', $currentGroup)) {
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

    public function setCustomerGroupCategoryVisibilityRepository(EntityRepository $repository)
    {
        $this->customerGroupCategoryVisibilityRepository = $repository;
    }

    /**
     * @return CustomerGroupCategoryRepository
     */
    public function getCustomerGroupCategoryVisibilityRepository()
    {
        return $this->customerGroupCategoryVisibilityRepository;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved');
    }

    /**
     * @return CategoryRepository
     */
    protected function getRepository()
    {
        return $this->repository;
    }
}
