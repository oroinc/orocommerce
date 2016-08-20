<?php

namespace Oro\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;

class VisibilityChangeGroupSubtreeCacheBuilder extends AbstractRelatedEntitiesAwareSubtreeCacheBuilder
{
    /** @var Category */
    protected $category;

    /** @var AccountGroup */
    protected $accountGroup;

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @param int $visibility
     */
    public function resolveVisibilitySettings(Category $category, AccountGroup $accountGroup, $visibility)
    {
        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category, $accountGroup);
        $this->updateGroupCategoryVisibility($childCategoryIds, $visibility, $accountGroup);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility, $accountGroup);

        $this->category = $category;
        $this->accountGroup = $accountGroup;

        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);

        $this->clearChangedEntities();
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param AccountGroup $accountGroup
     */
    protected function updateGroupCategoryVisibility(array $categoryIds, $visibility, AccountGroup $accountGroup)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved', 'agcvr')
            ->set('agcvr.visibility', $visibility)
            ->where($qb->expr()->eq('agcvr.accountGroup', ':accountGroup'))
            ->andWhere($qb->expr()->in('IDENTITY(agcvr.category)', ':categoryIds'))
            ->setParameters(['accountGroup' => $accountGroup, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountGroupsFirstLevel(Category $category, $visibility)
    {
        $accountGroupId = $this->accountGroup->getId();

        // if really first level - use account group
        if ($category->getId() === $this->category->getId()) {
            return [$accountGroupId];
        // if not - check if category visibility has fallback to original category
        } else {
            $parentCategory = $category->getParentCategory();
            if ($parentCategory && !empty($this->accountGroupIdsWithChangedVisibility[$parentCategory->getId()])) {
                $visibility = $this->registry
                    ->getManagerForClass('OroAccountBundle:Visibility\AccountGroupCategoryVisibility')
                    ->getRepository('OroAccountBundle:Visibility\AccountGroupCategoryVisibility')
                    ->getAccountGroupCategoryVisibility($this->accountGroup, $category);
                if ($visibility === AccountGroupCategoryVisibility::PARENT_CATEGORY) {
                    return [$accountGroupId];
                }
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountsFirstLevel(Category $category, $visibility)
    {
        // if not first level - check if category has fallback to original category
        if ($category->getId() != $this->category->getId()
            && empty($this->accountGroupIdsWithChangedVisibility[$category->getId()])
        ) {
            return [];
        }

        $accountIdsForUpdate = $this->getAccountIdsWithFallbackToCurrentGroup($category, $this->accountGroup);
        $this->updateAccountsProductVisibility($category, $accountIdsForUpdate, $visibility);

        return $accountIdsForUpdate;
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return array
     */
    protected function getAccountIdsWithFallbackToCurrentGroup(Category $category, AccountGroup $accountGroup)
    {
        /** @var Account[] $groupAccounts */
        $groupAccounts = $accountGroup->getAccounts()->toArray();
        if (empty($groupAccounts)) {
            return [];
        }

        $groupAccountIds = [];
        foreach ($groupAccounts as $account) {
            $groupAccountIds[] = $account->getId();
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroAccountBundle:Account')
            ->createQueryBuilder();

        $qb->select('account.id')
            ->from('OroAccountBundle:Account', 'account')
            ->leftJoin(
                'OroAccountBundle:Visibility\AccountCategoryVisibility',
                'accountCategoryVisibility',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('accountCategoryVisibility.account', 'account'),
                    $qb->expr()->eq('accountCategoryVisibility.category', ':category')
                )
            )
            ->where($qb->expr()->isNull('accountCategoryVisibility.id'))
            ->andWhere($qb->expr()->in('account', ':groupAccountIds'))
            ->setParameters([
                'category' => $category,
                'groupAccountIds' => $groupAccountIds
            ]);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictStaticFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->neq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountGroupCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * {@inheritdoc}
     */
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->eq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountGroupCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param AccountGroup $accountGroup
     */
    protected function updateProductVisibilityByCategory(array $categoryIds, $visibility, AccountGroup $accountGroup)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved', 'agpvr')
            ->set('agpvr.visibility', $visibility)
            ->where($qb->expr()->eq('agpvr.accountGroup', ':accountGroup'))
            ->andWhere($qb->expr()->in('IDENTITY(agpvr.category)', ':categoryIds'))
            ->setParameters(['accountGroup' => $accountGroup, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target = null)
    {
        return $qb->leftJoin(
            'OroAccountBundle:Visibility\AccountGroupCategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('node', 'cv.category'),
                $qb->expr()->eq('cv.accountGroup', ':accountGroup')
            )
        )
            ->setParameter('accountGroup', $target);
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
}
