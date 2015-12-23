<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Resolver;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityResolver implements CategoryVisibilityResolverInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function isCategoryVisible(Category $category)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->isCategoryVisible($category);
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleCategoryIds()
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * {@inheritdoc}
     */
    public function getHiddenCategoryIds()
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function isCategoryVisibleForAccountGroup(Category $category, AccountGroup $accountGroup)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->isCategoryVisible($category, $accountGroup);
    }

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getVisibleCategoryIdsForAccountGroup(AccountGroup $accountGroup)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $accountGroup);
    }

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getHiddenCategoryIdsForAccountGroup(AccountGroup $accountGroup)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN, $accountGroup);
    }

    /**
     * @param Category $category
     * @param Account|null $account
     * @return bool
     */
    public function isCategoryVisibleForAccount(Category $category, Account $account = null)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->isCategoryVisible($category, $account);
    }

    /**
     * @param Account $account
     * @return array
     */
    public function getVisibleCategoryIdsForAccount(Account $account)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $account);
    }

    /**
     * @param Account $account
     * @return array
     */
    public function getHiddenCategoryIdsForAccount(Account $account)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN, $account);
    }
}
