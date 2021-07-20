<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryVisibilityResolvedTermTrait;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolver;

class CategoryVisibilityQueryBuilderModifier
{
    use CategoryVisibilityResolvedTermTrait;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        ScopeManager $scopeManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
    }

    public function restrictForAnonymous(QueryBuilder $queryBuilder)
    {
        $categoryVisibilityConfigValue = $this->getCategoryVisibilityConfigValue();
        $categoryVisibilityTerm = $this->getCategoryVisibilityResolvedTerm(
            $queryBuilder,
            $categoryVisibilityConfigValue
        );
        $anonymousGroupVisibilityTerm = implode('+', [
            $categoryVisibilityTerm,
            $this->getCustomerGroupCategoryVisibilityResolvedTerm(
                $queryBuilder,
                $this->getAnonymousCustomerGroupScope(),
                $categoryVisibilityConfigValue
            )
        ]);

        $queryBuilder->andWhere($queryBuilder->expr()->gt($anonymousGroupVisibilityTerm, 0));
    }

    /**
     * @return int
     */
    private function getCategoryVisibilityConfigValue()
    {
        $categoryVisibility = $this->configManager->get(CategoryVisibilityResolver::OPTION_CATEGORY_VISIBILITY);
        return ($categoryVisibility === CategoryVisibility::HIDDEN)
            ? BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            : BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @return Scope
     */
    private function getAnonymousCustomerGroupScope()
    {
        $anonymousGroupId = $this->configManager->get('oro_customer.anonymous_customer_group');

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->doctrineHelper
            ->getEntityRepository(CustomerGroup::class)
            ->find($anonymousGroupId);

        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );

        return $scope;
    }
}
