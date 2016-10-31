<?php

namespace Oro\Bundle\CustomerBundle\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Visibility\ProductVisibilityTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;

class RestrictProductsIndexEventListener
{
    use ProductVisibilityTrait;
    use ContextTrait;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param string $productConfigPath
     * @param string $categoryConfigPath
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        $productConfigPath,
        $categoryConfigPath
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->productConfigPath = $productConfigPath;
        $this->categoryConfigPath = $categoryConfigPath;
    }

    /**
     * @param RestrictIndexEntityEvent $event
     */
    public function onRestrictIndexEntityEvent(RestrictIndexEntityEvent $event)
    {
        $context = $event->getContext();
        $websiteId = $this->requireContextCurrentWebsiteId($context);

        $qb = $event->getQueryBuilder();
        $qb->setParameter('website', $websiteId);

        $website = $this->doctrineHelper->getEntity(Website::class, $websiteId);

        $productVisibilityForAll = $this->getProductVisibilityResolvedTermByWebsite($qb, $website);

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->gt($productVisibilityForAll, 0),
                $this->getAccountProductVisibilitySubQuery($qb),
                $this->getAccountGroupProductVisibilitySubQuery($qb)
            )
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $visibilityClass
     * @param string $visibilityAlias
     * @return QueryBuilder
     */
    private function getVisibilityQueryBuilder(QueryBuilder $queryBuilder, $visibilityClass, $visibilityAlias)
    {
        $subQueryBuilder = $this->doctrineHelper
            ->getEntityRepository($visibilityClass)
            ->createQueryBuilder($visibilityAlias);

        $subQueryBuilder->andWhere(
            $subQueryBuilder->expr()->eq(sprintf('%s.product', $visibilityAlias), $this->getRootAlias($queryBuilder)),
            $subQueryBuilder->expr()->eq(sprintf('%s.website', $visibilityAlias), ':website')
        );

        return $subQueryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return Expr\Func
     */
    private function getAccountProductVisibilitySubQuery(QueryBuilder $queryBuilder)
    {
        $subQueryBuilder = $this->getVisibilityQueryBuilder(
            $queryBuilder,
            AccountProductVisibilityResolved::class,
            'account_product_visibility_resolved'
        );

        $subQueryBuilder->leftJoin(
            ProductVisibilityResolved::class,
            'sub_query_product_visibility_resolved',
            Expr\Join::WITH,
            $subQueryBuilder->expr()->andX(
                $subQueryBuilder->expr()->eq(
                    'account_product_visibility_resolved.product',
                    'sub_query_product_visibility_resolved.product'
                ),
                $subQueryBuilder->expr()->eq('sub_query_product_visibility_resolved.website', ':website')
            )
        );

        $productFallback = $this->addCategoryConfigFallback('sub_query_product_visibility_resolved.visibility');
        $accountFallback = $this->addCategoryConfigFallback('account_product_visibility_resolved.visibility');

        $visibilityTerm = $this->getAccountProductVisibilityResolvedVisibilityTerm($productFallback, $accountFallback);

        $subQueryBuilder->andWhere(
            $subQueryBuilder->expr()->gt($visibilityTerm, 0)
        );

        return $queryBuilder->expr()->exists($subQueryBuilder->getDQL());
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return Expr\Func
     */
    private function getAccountGroupProductVisibilitySubQuery(QueryBuilder $queryBuilder)
    {
        $subQueryBuilder = $this->getVisibilityQueryBuilder(
            $queryBuilder,
            AccountGroupProductVisibilityResolved::class,
            'account_group_product_visibility_resolved'
        );

        $subQueryBuilder->andWhere(
            $subQueryBuilder->expr()->gt($this->getAccountGroupProductVisibilityResolvedQueryPart(), 0)
        );

        return $queryBuilder->expr()->exists($subQueryBuilder->getDQL());
    }

    /**
     * @return string
     */
    private function getAccountGroupProductVisibilityResolvedQueryPart()
    {
        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('account_group_product_visibility_resolved.visibility')
        );
    }
}
