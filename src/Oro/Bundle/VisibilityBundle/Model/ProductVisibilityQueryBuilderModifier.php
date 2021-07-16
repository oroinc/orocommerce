<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\ProductVisibilityTrait;
use Oro\Component\Website\WebsiteInterface;

/**
 * Adds visibility resolved terms restrictions to product query builder.
 */
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

    public function __construct(
        ConfigManager $configManager,
        ScopeManager $scopeManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function modify(QueryBuilder $queryBuilder)
    {
        $visibilities[] = $this->getProductVisibilityResolvedTerm($queryBuilder);
        $visibilities[] = $this->getCustomerGroupProductVisibilityResolvedTerm($queryBuilder);
        $visibilities[] = $this->getCustomerProductVisibilityResolvedTerm($queryBuilder);
        $visibilityExpression = implode(' + ', $visibilities);

        $queryBuilder->andWhere($queryBuilder->expr()->gt($visibilityExpression, 0));
    }

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
        $queryBuilder->leftJoin(
            ProductVisibilityResolved::class,
            'product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq($this->getRootAlias($queryBuilder), 'product_visibility_resolved.product'),
                $queryBuilder->expr()->eq('product_visibility_resolved.scope', ':scope_all')
            )
        );

        $queryBuilder->setParameter(
            'scope_all',
            $this->getScopeId(ProductVisibility::VISIBILITY_TYPE)
        );

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
        $queryBuilder->leftJoin(
            CustomerGroupProductVisibilityResolved::class,
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

        $queryBuilder->setParameter(
            'scope_group',
            $this->getScopeId(CustomerGroupProductVisibility::VISIBILITY_TYPE)
        );

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
        $queryBuilder->leftJoin(
            CustomerProductVisibilityResolved::class,
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
        $queryBuilder->setParameter(
            'scope_customer',
            $this->getScopeId('customer_product_visibility')
        );

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

    /**
     * @param string $scopeType
     *
     * @return int The scope ID or 0 if the given scope does not exist
     */
    private function getScopeId($scopeType)
    {
        return $this->scopeManager->findId($scopeType) ?? 0;
    }
}
