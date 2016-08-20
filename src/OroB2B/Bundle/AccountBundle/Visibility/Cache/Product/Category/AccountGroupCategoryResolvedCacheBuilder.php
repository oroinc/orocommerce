<?php

namespace Oro\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository;
use Oro\Bundle\AccountBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use Oro\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupCategoryResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var VisibilityChangeGroupSubtreeCacheBuilder */
    protected $visibilityChangeAccountGroupSubtreeCacheBuilder;

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
        $accountGroup = $visibilitySettings->getAccountGroup();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['accountGroup' => $accountGroup, 'category' => $category];

        $repository = $this->getRepository();

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

            $visibility = $this->registry
                ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
                ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
                ->getFallbackToAllVisibility($category);
        } elseif ($selectedVisibility === AccountGroupCategoryVisibility::PARENT_CATEGORY) {
            list($visibility, $source) = $this->getParentCategoryVisibilityAndSource($category, $accountGroup);
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
            ->resolveVisibilitySettings($category, $accountGroup, $visibility);
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return array
     */
    protected function getParentCategoryVisibilityAndSource(Category $category, AccountGroup $accountGroup)
    {
        $parentCategory = $category->getParentCategory();
        if ($parentCategory) {
            return [
                $this->getRepository()->getFallbackToGroupVisibility($parentCategory, $accountGroup),
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
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
    }

    /**
     * @return AccountGroupCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
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
    public function buildCache(Website $website = null)
    {
        /** @var AccountGroupCategoryRepository $resolvedRepository */
        $resolvedRepository = $this->registry->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertFromSelectQueryExecutor);

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
            $resolvedRepository->insertParentCategoryValues($this->insertFromSelectQueryExecutor, $ids, $visibility);
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
}
