<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\DependencyInjection\Configuration;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryVisibilityResolvedTermTrait;

/**
 * Adds visibility resolved terms restrictions for anonymous users to category query builder.
 */
class CategoryVisibilityQueryBuilderModifier
{
    use CategoryVisibilityResolvedTermTrait;

    private const OPTION_CATEGORY_VISIBILITY = 'oro_visibility.category_visibility';

    private DoctrineHelper $doctrineHelper;
    private ConfigManager $configManager;
    private ScopeManager $scopeManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        ScopeManager $scopeManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
    }

    public function restrictForAnonymous(QueryBuilder $queryBuilder): void
    {
        $this->restrictForAnonymousUsingOrganization($queryBuilder);
    }

    public function restrictForAnonymousUsingOrganization(
        QueryBuilder $queryBuilder,
        ?OrganizationInterface $organization = null
    ): void {
        $categoryVisibilityConfigValue = $this->getCategoryVisibilityConfigValue();
        $categoryVisibilityTerm = $this->getCategoryVisibilityResolvedTerm(
            $queryBuilder,
            $categoryVisibilityConfigValue
        );
        $anonymousGroupVisibilityTerm = implode('+', [
            $categoryVisibilityTerm,
            $this->getCustomerGroupCategoryVisibilityResolvedTerm(
                $queryBuilder,
                $this->getAnonymousCustomerGroupScope($organization),
                $categoryVisibilityConfigValue
            )
        ]);

        $queryBuilder->andWhere($queryBuilder->expr()->gt($anonymousGroupVisibilityTerm, 0));
    }

    private function getCategoryVisibilityConfigValue(): int
    {
        return ($this->configManager->get(self::OPTION_CATEGORY_VISIBILITY) === CategoryVisibility::HIDDEN)
            ? BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            : BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    private function getAnonymousCustomerGroupScope(?OrganizationInterface $organization = null): Scope
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->doctrineHelper
            ->getEntityRepository(CustomerGroup::class)
            ->find($this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::ANONYMOUS_CUSTOMER_GROUP),
                false,
                false,
                $organization
            ));

        return $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
    }
}
