<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Resolver;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;

class CategoryVisibilityResolver implements CategoryVisibilityResolverInterface
{
    const OPTION_CATEGORY_VISIBILITY = 'oro_visibility.category_visibility';

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
     * @param CustomerGroup $customerGroup
     * @return bool
     */
    public function isCategoryVisibleForCustomerGroup(Category $category, CustomerGroup $customerGroup)
    {
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->isCategoryVisible(
                $category,
                $this->getCategoryVisibilityConfigValue(),
                $scope
            );
    }

    /**
     * @param CustomerGroup $customerGroup
     * @return array
     */
    public function getVisibleCategoryIdsForCustomerGroup(CustomerGroup $customerGroup)
    {
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $scope,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param CustomerGroup $customerGroup
     * @return array
     */
    public function getHiddenCategoryIdsForCustomerGroup(CustomerGroup $customerGroup)
    {
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $scope,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param Category $category
     * @param Customer $customer
     * @return bool
     */
    public function isCategoryVisibleForCustomer(Category $category, Customer $customer)
    {
        $scope = $this->scopeManager->findOrCreate(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $customerGroupScope = $this->getGroupScopeByCustomer($customer);

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->isCategoryVisible($category, $this->getCategoryVisibilityConfigValue(), $scope, $customerGroupScope);
    }

    /**
     * @param Customer $customer
     * @return array
     */
    public function getVisibleCategoryIdsForCustomer(Customer $customer)
    {
        $scope = $this->scopeManager->findOrCreate(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $groupScope = $this->getGroupScopeByCustomer($customer);

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $this->getCategoryVisibilityConfigValue(),
                $scope,
                $groupScope
            );
    }

    /**
     * @param Customer $customer
     * @return array
     */
    public function getHiddenCategoryIdsForCustomer(Customer $customer)
    {
        $scope = $this->scopeManager->findOrCreate(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $groupScope = $this->getGroupScopeByCustomer($customer);

        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $this->getCategoryVisibilityConfigValue(),
                $scope,
                $groupScope
            );
    }

    /**
     * @return int
     */
    protected function getCategoryVisibilityConfigValue()
    {
        return ($this->configManager->get(self::OPTION_CATEGORY_VISIBILITY) === CategoryVisibility::HIDDEN)
            ? BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            : BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param Customer $customer
     * @return null|\Oro\Bundle\ScopeBundle\Entity\Scope
     */
    protected function getGroupScopeByCustomer(Customer $customer)
    {
        $customerGroupScope = null;
        if ($customer->getGroup()) {
            $customerGroupScope = $this->scopeManager->find(
                CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
                ['customerGroup' => $customer->getGroup()]
            );
        }
        return $customerGroupScope;
    }
}
