<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountGroupCategoryVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\RepositoryHolder;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;

class AccountGroupCategoryResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var VisibilityChangeGroupSubtreeCacheBuilder */
    protected $visibilityChangeAccountGroupSubtreeCacheBuilder;

    /**
     * @var RepositoryHolder
     */
    protected $accountGroupCategoryVisibilityHolder;

    /**
     * @param VisibilityChangeGroupSubtreeCacheBuilder $visibilityChangeAccountGroupSubtreeCacheBuilder
     */
    public function setVisibilityChangeAccountSubtreeCacheBuilder(
        VisibilityChangeGroupSubtreeCacheBuilder $visibilityChangeAccountGroupSubtreeCacheBuilder
    ) {
        $this->visibilityChangeAccountGroupSubtreeCacheBuilder = $visibilityChangeAccountGroupSubtreeCacheBuilder;
    }

    /**
     * @param VisibilityInterface|AccountGroupCategoryVisibility $visibilitySettings
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

        $repository = $this->getAccountGroupCategoryVisibilityRepository();

        $hasAccountGroupCategoryVisibilityResolved = $repository->hasEntity($where);

        if (!$hasAccountGroupCategoryVisibilityResolved
            && $selectedVisibility !== AccountGroupCategoryVisibility::CATEGORY
        ) {
            $insert = true;
        }

        if (in_array(
            $selectedVisibility,
            [
                AccountGroupCategoryVisibility::HIDDEN,
                AccountGroupCategoryVisibility::VISIBLE
            ]
        )) {
            $visibility = $this->convertStaticVisibility($selectedVisibility);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => AccountGroupCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility === AccountGroupCategoryVisibility::CATEGORY) {
            // fallback to category is default for account group and should be removed if exists
            if ($hasAccountGroupCategoryVisibilityResolved) {
                $delete = true;
            }

            $visibility = $this->getRepository()->getFallbackToAllVisibility($category);
        } elseif ($selectedVisibility === AccountGroupCategoryVisibility::PARENT_CATEGORY) {
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

        $this->visibilityChangeAccountGroupSubtreeCacheBuilder
            ->resolveVisibilitySettings($category, $scope, $visibility);
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
                $this->getAccountGroupCategoryVisibilityRepository()
                    ->getFallbackToGroupVisibility($parentCategory, $scope),
                AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            ];
        } else {
            return [
                AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                AccountGroupCategoryVisibilityResolved::SOURCE_STATIC
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof AccountGroupCategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Scope $scope = null)
    {
        $resolvedRepository = $this->getAccountGroupCategoryVisibilityRepository();

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues();

        // resolve parent category values
        $groupVisibilities = $this->indexVisibilities(
            $resolvedRepository->getParentCategoryVisibilities(),
            'visibility_id'
        );
        $groupVisibilityIds = [
            AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE => [],
            AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN => [],
            AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG => [],
        ];
        foreach ($groupVisibilities as $visibilityId => $groupVisibility) {
            $resolvedVisibility = $this->resolveVisibility($groupVisibilities, $groupVisibility);
            $groupVisibilityIds[$resolvedVisibility][] = $visibilityId;
        }
        foreach ($groupVisibilityIds as $visibility => $ids) {
            $resolvedRepository->insertParentCategoryValues($ids, $visibility);
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
        } elseif ($parentVisibility === AccountGroupCategoryVisibility::PARENT_CATEGORY) {
            $parentGroup = $groupVisibilities[$parentVisibilityId];
            $resolvedVisibility = $this->resolveVisibility($groupVisibilities, $parentGroup);
        // static visibility
        } else {
            $resolvedVisibility
                = $this->convertVisibility($parentVisibility === AccountGroupCategoryVisibility::VISIBLE);
        }

        // config value (default)
        if (null === $resolvedVisibility) {
            $resolvedVisibility = AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        $groupVisibilities[$visibilityId]['resolved_visibility'] = $resolvedVisibility;

        return $resolvedVisibility;
    }

    /**
     * @param RepositoryHolder $categoryVisibilityHolder
     */
    public function setAccountGroupCategoryVisibilityHolder($categoryVisibilityHolder)
    {
        $this->accountGroupCategoryVisibilityHolder = $categoryVisibilityHolder;
    }

    /**
     * @return AccountGroupCategoryVisibilityRepository
     */
    protected function getAccountGroupCategoryVisibilityRepository()
    {
        return $this->accountGroupCategoryVisibilityHolder->getRepository();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
    }

    /**
     * @return CategoryRepository
     */
    protected function getRepository()
    {
        return $this->repositoryHolder->getRepository();
    }
}
