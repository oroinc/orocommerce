<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Resolver;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;

class CategoryVisibilityResolver implements CategoryVisibilityResolverInterface
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

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
     * @param ScopeManager $scopeManager
     */
    public function __construct(Registry $registry, ConfigManager $configManager, ScopeManager $scopeManager)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isCategoryVisible(Category $category)
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->isCategoryVisible($category, $this->getCategoryVisibilityConfigValue());
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleCategoryIds()
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
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
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
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
        $scope = $this->scopeManager->findOrCreate('category_visibility', $accountGroup);

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->isCategoryVisible(
                $category,
                $scope,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getVisibleCategoryIdsForAccountGroup(AccountGroup $accountGroup)
    {
        $scope = $this->scopeManager->findOrCreate('category_visibility', $accountGroup);

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $scope,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getHiddenCategoryIdsForAccountGroup(AccountGroup $accountGroup)
    {
        $scope = $this->scopeManager->findOrCreate('category_visibility', $accountGroup);

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $scope,
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
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->isCategoryVisible($category, $account, $this->getCategoryVisibilityConfigValue());
    }

    /**
     * @param Account $account
     * @return array
     */
    public function getVisibleCategoryIdsForAccount(Account $account)
    {
        $scope = $this->scopeManager->findOrCreate('category_visibility', $account);

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $scope,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param Account $account
     * @return array
     */
    public function getHiddenCategoryIdsForAccount(Account $account)
    {
        $scope = $this->scopeManager->findOrCreate('category_visibility', $account);
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $scope,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @return int
     */
    protected function getCategoryVisibilityConfigValue()
    {
        return ($this->configManager->get('oro_visibility.category_visibility') === CategoryVisibility::HIDDEN)
            ? BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            : BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }
}
