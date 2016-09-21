<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Resolver;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\VisibilityBundle\Entity\Account;
use Oro\Bundle\VisibilityBundle\Entity\AccountGroup;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityResolver implements CategoryVisibilityResolverInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param Registry $registry
     * @param ConfigManager $configManager
     */
    public function __construct(Registry $registry, ConfigManager $configManager)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isCategoryVisible(Category $category)
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->isCategoryVisible($category, $this->getCategoryVisibilityConfigValue());
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleCategoryIds()
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getHiddenCategoryIds()
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function isCategoryVisibleForAccountGroup(Category $category, AccountGroup $accountGroup)
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->isCategoryVisible(
                $category,
                $accountGroup,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getVisibleCategoryIdsForAccountGroup(AccountGroup $accountGroup)
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $accountGroup,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getHiddenCategoryIdsForAccountGroup(AccountGroup $accountGroup)
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $accountGroup,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param Category $category
     * @param Account $account
     * @return bool
     */
    public function isCategoryVisibleForAccount(Category $category, Account $account)
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->isCategoryVisible($category, $account, $this->getCategoryVisibilityConfigValue());
    }

    /**
     * @param Account $account
     * @return array
     */
    public function getVisibleCategoryIdsForAccount(Account $account)
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $account,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param Account $account
     * @return array
     */
    public function getHiddenCategoryIdsForAccount(Account $account)
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $account,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @return int
     */
    protected function getCategoryVisibilityConfigValue()
    {
        return ($this->configManager->get('oro_account.category_visibility') === CategoryVisibility::HIDDEN)
            ? BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            : BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }
}
