<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Visibility\ProductVisibilityTrait;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;

class ProductVisibilityQueryBuilderModifier implements QueryBuilderModifierInterface
{
    use ProductVisibilityTrait;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param ConfigManager $configManager
     * @param ScopeManager $scopeManager
     */
    public function __construct(ConfigManager $configManager, ScopeManager $scopeManager)
    {
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function modify(QueryBuilder $queryBuilder)
    {
        $visibilities[] = $this->getProductVisibilityResolvedTerm($queryBuilder);
        $visibilities[] = $this->getAccountGroupProductVisibilityResolvedTerm($queryBuilder);
        $visibilities[] = $this->getAccountProductVisibilityResolvedTerm($queryBuilder);

        $queryBuilder->andWhere($queryBuilder->expr()->gt(implode(' + ', $visibilities), 0));
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
    protected function getAccountGroupProductVisibilityResolvedTerm(
        QueryBuilder $queryBuilder
    ) {
        $scope = $this->scopeManager->find(AccountGroupProductVisibility::VISIBILITY_TYPE);
        if (!$scope) {
            $scope = 0;
        }
        $queryBuilder->leftJoin(
            'Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved',
            'account_group_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_group_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('account_group_product_visibility_resolved.scope', ':scope_group')
            )
        );

        $queryBuilder->setParameter('scope_group', $scope);

        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('account_group_product_visibility_resolved.visibility')
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    protected function getAccountProductVisibilityResolvedTerm(QueryBuilder $queryBuilder)
    {
        $scope = $this->scopeManager->find('account_product_visibility');
        if (!$scope) {
            $scope = 0;
        }
        $queryBuilder->leftJoin(
            'Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved',
            'account_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('account_product_visibility_resolved.scope', ':scope_account')
            )
        );
        $queryBuilder->setParameter('scope_account', $scope);

        $productFallback = $this->addCategoryConfigFallback('product_visibility_resolved.visibility');
        $accountFallback = $this->addCategoryConfigFallback('account_product_visibility_resolved.visibility');

        $term = <<<TERM
CASE WHEN account_product_visibility_resolved.visibility = %s
    THEN (COALESCE(%s, %s) * 100)
ELSE (COALESCE(%s, 0) * 100)
END
TERM;
        return sprintf(
            $term,
            AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $productFallback,
            $this->getProductConfigValue(),
            $accountFallback
        );
    }
}
