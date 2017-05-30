<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\VisibilityBundle\Visibility\ProductVisibilityTrait;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Component\Website\WebsiteInterface;

class ProductVisibilityQueryBuilderModifier implements QueryBuilderModifierInterface
{
    use ProductVisibilityTrait;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param ConfigManager $configManager
     * @param ScopeManager $scopeManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ConfigManager $configManager,
        ScopeManager $scopeManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function modify(QueryBuilder $queryBuilder)
    {
        $visibilities[] = $this->getProductVisibilityResolvedTerm($queryBuilder);
        $visibilities[] = $this->getCustomerGroupProductVisibilityResolvedTerm($queryBuilder);
        $visibilities[] = $this->getCustomerProductVisibilityResolvedTerm($queryBuilder);

        $queryBuilder->andWhere($queryBuilder->expr()->gt(implode(' + ', $visibilities), 0));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param WebsiteInterface $website
     */
    public function restrictForAnonymous(QueryBuilder $queryBuilder, WebsiteInterface $website)
    {
        $productVisibilityTerm = $this->getProductVisibilityResolvedTermByWebsite(
            $queryBuilder,
            $website
        );
        $anonymousGroupVisibilityTerm = implode('+', [
            $productVisibilityTerm,
            $this->getCustomerGroupProductVisibilityResolvedTermByWebsite(
                $queryBuilder,
                $this->getAnonymousCustomerGroup(),
                $website
            )
        ]);

        $queryBuilder->andWhere($queryBuilder->expr()->gt($anonymousGroupVisibilityTerm, 0));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    protected function getProductVisibilityResolvedTerm(QueryBuilder $queryBuilder)
    {
        $scope = $this->scopeManager->find(ProductVisibility::VISIBILITY_TYPE);
        if (!$scope) {
            $scope = 0;
        }
        $queryBuilder->leftJoin(
            'Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved',
            'product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq($this->getRootAlias($queryBuilder), 'product_visibility_resolved.product'),
                $queryBuilder->expr()->eq('product_visibility_resolved.scope', ':scope_all')
            )
        );

        $queryBuilder->setParameter('scope_all', $scope);

        return sprintf(
            'COALESCE(%s, %s)',
            $this->addCategoryConfigFallback('product_visibility_resolved.visibility'),
            $this->getProductConfigValue()
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    protected function getCustomerGroupProductVisibilityResolvedTerm(
        QueryBuilder $queryBuilder
    ) {
        $scope = $this->scopeManager->find(CustomerGroupProductVisibility::VISIBILITY_TYPE);
        if (!$scope) {
            $scope = 0;
        }
        $queryBuilder->leftJoin(
            'Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved',
            'customer_group_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'customer_group_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('customer_group_product_visibility_resolved.scope', ':scope_group')
            )
        );

        $queryBuilder->setParameter('scope_group', $scope);

        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('customer_group_product_visibility_resolved.visibility')
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    protected function getCustomerProductVisibilityResolvedTerm(QueryBuilder $queryBuilder)
    {
        $scope = $this->scopeManager->find('customer_product_visibility');
        if (!$scope) {
            $scope = 0;
        }
        $queryBuilder->leftJoin(
            'Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved',
            'customer_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'customer_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('customer_product_visibility_resolved.scope', ':scope_customer')
            )
        );
        $queryBuilder->setParameter('scope_customer', $scope);

        $productFallback = $this->addCategoryConfigFallback('product_visibility_resolved.visibility');
        $customerFallback = $this->addCategoryConfigFallback('customer_product_visibility_resolved.visibility');

        $term = <<<TERM
CASE WHEN customer_product_visibility_resolved.visibility = %s
    THEN (COALESCE(%s, %s) * 100)
ELSE (COALESCE(%s, 0) * 100)
END
TERM;
        return sprintf(
            $term,
            CustomerProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $productFallback,
            $this->getProductConfigValue(),
            $customerFallback
        );
    }

    /**
     * @return CustomerGroup|null
     */
    private function getAnonymousCustomerGroup()
    {
        $anonymousGroupId = $this->configManager->get('oro_customer.anonymous_customer_group');

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->doctrineHelper
            ->getEntityRepository(CustomerGroup::class)
            ->find($anonymousGroupId);

        return $customerGroup;
    }
}
