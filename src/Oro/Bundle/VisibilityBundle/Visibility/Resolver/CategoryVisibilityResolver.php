<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

/**
 * Provides a set of methods to help resolving visibilities of categories.
 */
class CategoryVisibilityResolver implements CategoryVisibilityResolverInterface
{
    private const OPTION_CATEGORY_VISIBILITY = 'oro_visibility.category_visibility';

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ConfigManager */
    private $configManager;

    /** @var ScopeManager */
    private $scopeManager;

    public function __construct(ManagerRegistry $doctrine, ConfigManager $configManager, ScopeManager $scopeManager)
    {
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isCategoryVisible(Category $category)
    {
        return $this->getCategoryRepository()
            ->isCategoryVisible($category, $this->getCategoryVisibilityConfigValue());
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleCategoryIds()
    {
        return $this->getCategoryRepository()
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
        return $this->getCategoryRepository()
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param Category      $category
     * @param CustomerGroup $customerGroup
     *
     * @return bool
     */
    public function isCategoryVisibleForCustomerGroup(Category $category, CustomerGroup $customerGroup)
    {
        $scope = $this->getScope(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );

        return $this->getCustomerGroupCategoryRepository()
            ->isCategoryVisible(
                $category,
                $this->getCategoryVisibilityConfigValue(),
                $scope
            );
    }

    /**
     * @param CustomerGroup $customerGroup
     *
     * @return array
     */
    public function getVisibleCategoryIdsForCustomerGroup(CustomerGroup $customerGroup)
    {
        $scope = $this->getScope(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );

        return $this->getCustomerGroupCategoryRepository()
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $scope,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param CustomerGroup $customerGroup
     *
     * @return array
     */
    public function getHiddenCategoryIdsForCustomerGroup(CustomerGroup $customerGroup)
    {
        $scope = $this->getScope(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );

        return $this->getCustomerGroupCategoryRepository()
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $scope,
                $this->getCategoryVisibilityConfigValue()
            );
    }

    /**
     * @param Category $category
     * @param Customer $customer
     *
     * @return bool
     */
    public function isCategoryVisibleForCustomer(Category $category, Customer $customer)
    {
        $scope = $this->getScope(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $customerGroupScope = $this->getGroupScopeByCustomer($customer);

        return $this->getCustomerCategoryRepository()
            ->isCategoryVisible($category, $this->getCategoryVisibilityConfigValue(), $scope, $customerGroupScope);
    }

    /**
     * @param Customer $customer
     *
     * @return array
     */
    public function getVisibleCategoryIdsForCustomer(Customer $customer)
    {
        $scope = $this->getScope(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $groupScope = $this->getGroupScopeByCustomer($customer);

        return $this->getCustomerCategoryRepository()
            ->getCategoryIdsByVisibility(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $this->getCategoryVisibilityConfigValue(),
                $scope,
                $groupScope
            );
    }

    /**
     * @param Customer $customer
     *
     * @return array
     */
    public function getHiddenCategoryIdsForCustomer(Customer $customer)
    {
        $scope = $this->getScope(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $groupScope = $this->getGroupScopeByCustomer($customer);

        return $this->getCustomerCategoryRepository()
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
    private function getCategoryVisibilityConfigValue()
    {
        return ($this->configManager->get(self::OPTION_CATEGORY_VISIBILITY) === CategoryVisibility::HIDDEN)
            ? BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            : BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    private function findScope(string $scopeType, array $context): ?Scope
    {
        // by performance reasons, use findId() + getReference() instead of find()
        $scopeId = $this->scopeManager->findId($scopeType, $context);
        if (null === $scopeId) {
            return null;
        }

        return $this->doctrine->getManagerForClass(Scope::class)->getReference(Scope::class, $scopeId);
    }

    private function getScope(string $scopeType, array $context): Scope
    {
        // by performance reasons, use findId() + createScopeByCriteria() instead of findOrCreate()
        $scope = $this->findScope($scopeType, $context);
        if (null === $scope) {
            $scope = $this->scopeManager->createScopeByCriteria(
                $this->scopeManager->getCriteria($scopeType, $context)
            );
        }

        return $scope;
    }

    /**
     * @param Customer $customer
     *
     * @return Scope|null
     */
    private function getGroupScopeByCustomer(Customer $customer)
    {
        if (!$customer->getGroup()) {
            return null;
        }

        return $this->findScope(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customer->getGroup()]
        );
    }

    private function getCategoryRepository(): Repository\CategoryRepository
    {
        return $this->doctrine
            ->getManagerForClass(VisibilityResolved\CategoryVisibilityResolved::class)
            ->getRepository(VisibilityResolved\CategoryVisibilityResolved::class);
    }

    private function getCustomerGroupCategoryRepository(): Repository\CustomerGroupCategoryRepository
    {
        return $this->doctrine
            ->getManagerForClass(VisibilityResolved\CustomerGroupCategoryVisibilityResolved::class)
            ->getRepository(VisibilityResolved\CustomerGroupCategoryVisibilityResolved::class);
    }

    private function getCustomerCategoryRepository(): Repository\CustomerCategoryRepository
    {
        return $this->doctrine
            ->getManagerForClass(VisibilityResolved\CustomerCategoryVisibilityResolved::class)
            ->getRepository(VisibilityResolved\CustomerCategoryVisibilityResolved::class);
    }
}
