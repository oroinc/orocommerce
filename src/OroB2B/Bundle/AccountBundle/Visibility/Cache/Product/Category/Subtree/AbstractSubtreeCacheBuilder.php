<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolver;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

abstract class AbstractSubtreeCacheBuilder
{
    /** @var array */
    protected $accountGroupsWithChangedVisibility;

    /** @var array */
    protected $accountsWithChangedVisibility;

    /**
     * @param Registry $registry
     * @param CategoryVisibilityResolver $categoryVisibilityResolver
     */
    public function __construct(Registry $registry, CategoryVisibilityResolver $categoryVisibilityResolver)
    {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }

    /**
     * @param bool $visibility
     * @return int
     */
    protected function convertVisibility($visibility)
    {
        return $visibility
            ? BaseProductVisibilityResolved::VISIBILITY_VISIBLE
            : BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateProductVisibilitiesForCategoryRelatedEntities(Category $category, $visibility)
    {
        $childCategories = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getAllChildCategories($category);

        $childCategoryLevels = [];
        foreach ($childCategories as $category) {
            $childCategoryLevels[$category->getLevel()][] = $category;
        }

        $firstSubCategoryLevel = $category->getLevel() + 1;
        if (!empty($childCategoryLevels)) {
            for ($level = $firstSubCategoryLevel; $level <= max(array_keys($childCategoryLevels)); $level++) {
                $this->updateChildCategoriesByLevel($childCategoryLevels[$level], $visibility);
            }
        }

        unset($childCategories);

        $childCategoriesWithFallbackToParent = $this->getChildCategoriesWithFallbackToParent($category);
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
    protected function getChildCategoriesWithFallbackToParent(Category $category)
    {
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilder($category, false, ['level']);

        /** @var QueryBuilder $subQueryQb */
        $subQueryQb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility')
            ->createQueryBuilder();

        $subQuery = $subQueryQb->select('IDENTITY(categoryVisibility.category)')
            ->from(
                'OroB2BAccountBundle:Visibility\CategoryVisibility',
                'categoryVisibility'
            )
            ->where($subQueryQb->expr()->eq('categoryVisibility.category', ':category'))
            ->distinct();

        $qb->andWhere($qb->expr()->notIn('node', $subQuery->getDQL()))
            ->setParameter('category', $category);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Category $category
     * @param $target
     * @return array
     */
    protected function getCategoryIdsForUpdate(Category $category, $target)
    {
        $categoriesWithStaticFallback = $this->getChildCategoriesWithStaticFallback($category, $target);
        $childCategories = $this->getChildCategoriesWithToParentFallback(
            $category,
            $categoriesWithStaticFallback,
            $target
        );

        $categoryIds = array_map(
            function ($category) {
                return $category['id'];
            },
            $childCategories
        );

        $categoryIds[] = $category->getId();

        return $categoryIds;
    }

    /**
     * @param Category $category
     * @param object|null $target
     * @return array
     */
    protected function getChildCategoriesWithStaticFallback(Category $category, $target)
    {
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilder($category);

        $qb = $this->joinCategoryVisibility($qb, $target);
        $qb = $this->restrictStaticFallback($qb);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param Category $category
     * @param array $categoriesWithStaticFallback
     * @param $target
     * @return array
     */
    protected function getChildCategoriesWithToParentFallback(
        Category $category,
        array $categoriesWithStaticFallback,
        $target
    ) {
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilder($category);

        $qb = $this->joinCategoryVisibility($qb, $target);
        $qb = $this->restrictToParentFallback($qb);

        foreach ($categoriesWithStaticFallback as $node) {
            $qb->andWhere(
                $qb->expr()->not(
                    $qb->expr()->andX(
                        $qb->expr()->gt('node.level', $node['level']),
                        $qb->expr()->gt('node.left', $node['left']),
                        $qb->expr()->lt('node.right', $node['right'])
                    )
                )
            );
        }

        return $qb->getQuery()->getArrayResult();
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

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    abstract protected function restrictStaticFallback(QueryBuilder $qb);

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    abstract protected function restrictToParentFallback(QueryBuilder $qb);

    /**
     * @param QueryBuilder $qb
     * @param object|null $target
     * @return QueryBuilder
     */
    abstract protected function joinCategoryVisibility(QueryBuilder $qb, $target);
}
