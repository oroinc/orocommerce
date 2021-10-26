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

    private function getCategoryVisibilityConfigValue(): int
    {
        return ($this->configManager->get(self::OPTION_CATEGORY_VISIBILITY) === CategoryVisibility::HIDDEN)
            ? BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            : BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    private function getAnonymousCustomerGroupScope(): Scope
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->doctrineHelper
            ->getEntityRepository(CustomerGroup::class)
            ->find($this->configManager->get('oro_customer.anonymous_customer_group'));

        return $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
    }
}
