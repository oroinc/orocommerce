<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Calculator;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

class CategoryVisibilityResolverAdapter implements CategoryVisibilityResolverAdapterInterface
{
    /** @var  CategoryVisibilityResolverInterface */
    protected $categoryVisibilityResolver;

    /**
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     */
    public function __construct(CategoryVisibilityResolverInterface $categoryVisibilityResolver)
    {
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategoryVisibility(Category $category)
    {
        return $this->categoryVisibilityResolver->isCategoryVisible($category) ?
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
            : BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategoryVisibilityForAccount(Category $category, Account $account)
    {
        return $this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account) ?
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
            : BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategoryVisibilityForAccountGroup(Category $category, AccountGroup $accountGroup)
    {
        return $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup) ?
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
            : BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
    }
}
