<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

abstract class AbstractRelatedEntitiesAwareSubtreeCacheBuilder extends AbstractSubtreeCacheBuilder
{
    /** @var array */
    protected $accountGroupsWithChangedVisibility;

    /** @var array */
    protected $accountsWithChangedVisibility;

    /**
     * {@inheritdoc}
     */
    abstract protected function updateAccountGroupsFirstLevel(Category $category, $visibility);

    /**
     * {@inheritdoc}
     */
    abstract protected function updateAccountsFirstLevel(Category $category, $visibility);

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateProductVisibilitiesForCategoryRelatedEntities(Category $category, $visibility)
    {
        $this->accountGroupsWithChangedVisibility[$category->getId()]
            = $this->updateAccountGroupsFirstLevel($category, $visibility);

        $this->accountsWithChangedVisibility[$category->getId()]
            = $this->updateAccountsFirstLevel($category, $visibility);

        $childCategories = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getAllChildCategories($category);

        $childCategoryLevels = [];
        /** @var Category[] $childCategories */
        foreach ($childCategories as $childCategory) {
            $childCategoryLevels[$childCategory->getLevel()][] = $childCategory;
        }

        $firstSubCategoryLevel = $category->getLevel() + 1;
        if (!empty($childCategoryLevels)) {
            for ($level = $firstSubCategoryLevel; $level <= max(array_keys($childCategoryLevels)); $level++) {
                $this->updateChildCategoriesByLevel($childCategoryLevels[$level], $visibility);
            }
        }

        unset($childCategories);

        $childCategoriesWithFallbackToParent = $this->getDirectChildCategoriesWithFallbackToParent($category);
        foreach ($childCategoriesWithFallbackToParent as $category) {
            $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);
        }
    }

    /**
     * @param array $childCategoryLevels
     * @param int $visibility
     */
    protected function updateChildCategoriesByLevel($childCategoryLevels, $visibility)
    {
        /** @var Category $levelCategory */
        foreach ($childCategoryLevels as $levelCategory) {
            $accountGroupsWithFallbackToParent = $this
                ->getCategoryAccountGroupsWithVisibilityFallbackToParent($levelCategory);
            $parentAccountGroups
                = $this->accountGroupsWithChangedVisibility[$levelCategory->getParentCategory()->getId()];

            $updatedAccountGroups = $this->intersectRelatedEntities(
                $parentAccountGroups,
                $accountGroupsWithFallbackToParent
            );

            $this->accountGroupsWithChangedVisibility[$levelCategory->getId()] = $updatedAccountGroups;

            $this->updateAccountGroupsProductVisibility($levelCategory, $updatedAccountGroups, $visibility);

            $accountsWithFallbackToParent = $this->getAccountsWithFallbackToParent($levelCategory);

            $accountsForUpdate = $this->intersectRelatedEntities(
                $accountsWithFallbackToParent,
                $this->accountsWithChangedVisibility[$levelCategory->getParentCategory()->getId()]
            );

            if (!empty($updatedAccountGroups)) {
                $updatedAccountGroupIds = [];
                foreach ($updatedAccountGroups as $updatedAccountGroup) {
                    $updatedAccountGroupIds[] = $updatedAccountGroup->getId();
                }
                $accountsForUpdate = array_merge(
                    $accountsForUpdate,
                    $this->getAccountsForUpdate($levelCategory, $updatedAccountGroupIds)
                );
            }

            /**
             * Cache updated account for current subcategory into appropriate section
             */
            $this->accountsWithChangedVisibility[$levelCategory->getId()] = $accountsForUpdate;

            $this->updateAccountsProductVisibility($levelCategory, $accountsForUpdate, $visibility);
        }
    }

    /**
     * @param Category $category
     * @return AccountGroup[]
     */
    protected function getCategoryAccountGroupsWithVisibilityFallbackToParent(Category $category)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->getRepository('OroB2BAccountBundle:AccountGroup')
            ->getCategoryAccountGroupsByVisibility($category, AccountGroupCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param Category $category
     * @return Account[]
     */
    protected function getAccountsWithFallbackToParent(Category $category)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->getRepository('OroB2BAccountBundle:Account')
            ->getCategoryAccountsByVisibility($category, AccountCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param Category $category
     * @return Account[]
     */
    protected function getAccountsWithFallbackToALL(Category $category)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->getRepository('OroB2BAccountBundle:Account')
            ->getCategoryAccountsByVisibility($category, AccountCategoryVisibility::CATEGORY);
    }

    /**
     * @param AccountGroup[]|Account[] $relatedEntitiesWithFallbackToAll
     * @param AccountGroup[]|Account[] $relatedEntitiesWithFallbackToParent
     * @return AccountGroup[]|Account[]
     */
    protected function intersectRelatedEntities(
        array $relatedEntitiesWithFallbackToAll,
        array $relatedEntitiesWithFallbackToParent
    ) {
        $result = [];
        foreach ($relatedEntitiesWithFallbackToAll as $entity) {
            if (in_array($entity, $relatedEntitiesWithFallbackToParent)) {
                $result[] = $entity;
            }
        }

        return $result;
    }

    /**
     * @param Category $category
     * @param AccountGroup[] $accountGroups
     * @return Account[]
     */
    protected function getAccountsForUpdate(Category $category, array $accountGroups)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->createQueryBuilder();

        /** @var QueryBuilder $subQueryQb */
        $subQueryQb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
            ->createQueryBuilder();

        $subQuery = $subQueryQb->select('IDENTITY(accountCategoryVisibility.account)')
            ->from(
                'OroB2BAccountBundle:Visibility\AccountCategoryVisibility',
                'accountCategoryVisibility'
            )
            ->where($subQueryQb->expr()->eq('accountCategoryVisibility.category', ':category'))
            ->distinct();

        $qb->select('account')
            ->from('OroB2BAccountBundle:Account', 'account')
            ->leftJoin('account.group', 'accountGroup')
            ->where($qb->expr()->notIn('account', $subQuery->getDQL()))
            ->andWhere($qb->expr()->in('accountGroup', $accountGroups))
            ->setParameter('category', $category);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Category $category
     * @return Category[]
     */
    protected function getDirectChildCategoriesWithFallbackToParent(Category $category)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilder($category);

        $qb->leftJoin(
            'OroB2BAccountBundle:Visibility\CategoryVisibility',
            'categoryVisibility',
            Join::WITH,
            $qb->expr()->eq('node.id', 'categoryVisibility.category')
        )
        ->andWhere($qb->expr()->isNull('categoryVisibility.visibility'))
        ->andWhere($qb->expr()->eq('node.parentCategory', ':category'))
        ->setParameter('category', $category);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Category $category
     * @param AccountGroup[] $accountGroups
     * @param int $visibility
     */
    protected function updateAccountGroupsProductVisibility(Category $category, array $accountGroups, $visibility)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved', 'agpvr')
            ->set('agpvr.visibility', $visibility)
            ->where($qb->expr()->eq('agpvr.categoryId', $category->getId()))
            ->andWhere($qb->expr()->in('agpvr.accountGroup', ':accountGroups'))
            ->setParameter('accountGroups', $accountGroups);

        $qb->getQuery()->execute();
    }

    /**
     * @param Category $category
     * @param Account[] $accounts
     * @param $visibility
     */
    protected function updateAccountsProductVisibility(Category $category, array $accounts, $visibility)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved', 'apvr')
            ->set('apvr.visibility', $visibility)
            ->where($qb->expr()->eq('apvr.categoryId', $category->getId()))
            ->andWhere($qb->expr()->in('apvr.account', ':accounts'))
            ->setParameter('accounts', $accounts);

        $qb->getQuery()->execute();
    }
}
