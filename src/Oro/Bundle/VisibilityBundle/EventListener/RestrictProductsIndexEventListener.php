<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\ProductVisibilityTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

class RestrictProductsIndexEventListener
{
    use ProductVisibilityTrait;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var  WebsiteContextManager */
    private $websiteContextManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param string $productConfigPath
     * @param string $categoryConfigPath
     * @param WebsiteContextManager $websiteContextManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        $productConfigPath,
        $categoryConfigPath,
        WebsiteContextManager $websiteContextManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->productConfigPath = $productConfigPath;
        $this->categoryConfigPath = $categoryConfigPath;
        $this->websiteContextManager = $websiteContextManager;
    }

    /**
     * @param RestrictIndexEntityEvent $event
     */
    public function onRestrictIndexEntityEvent(RestrictIndexEntityEvent $event)
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        $qb = $event->getQueryBuilder();
        $website = $this->doctrineHelper->getEntity(Website::class, $websiteId);

        $productVisibilityForAll = $this->getProductVisibilityResolvedTermByWebsite($qb, $website);

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->gt($productVisibilityForAll, 0),
                $this->getAccountProductVisibilitySubQuery($qb, $websiteId),
                $this->getAccountGroupProductVisibilitySubQuery($qb, $websiteId)
            )
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $visibilityClass
     * @param string $visibilityAlias
     * @param int $websiteId
     * @return QueryBuilder
     */
    private function getVisibilityQueryBuilder(
        QueryBuilder $queryBuilder,
        $visibilityClass,
        $visibilityAlias,
        $websiteId
    ) {
        $subQueryBuilder = $this->doctrineHelper
            ->getEntityRepository($visibilityClass)
            ->createQueryBuilder($visibilityAlias);

        $visibilityScopeAlias = sprintf('visibilityScope%s', $visibilityAlias);

        $subQueryBuilder
            ->innerJoin(
                sprintf('%s.scope', $visibilityAlias),
                $visibilityScopeAlias,
                Expr\Join::WITH,
                $subQueryBuilder->expr()->orX(
                    $subQueryBuilder->expr()->eq($visibilityScopeAlias . '.website', ':visibilityWebsite'),
                    $subQueryBuilder->expr()->isNull($visibilityScopeAlias . '.website')
                )
            );

        $queryBuilder->setParameter('visibilityWebsite', $websiteId);

        $subQueryBuilder->andWhere(
            $subQueryBuilder->expr()->eq(sprintf('%s.product', $visibilityAlias), $this->getRootAlias($queryBuilder))
        );

        return $subQueryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $websiteId
     * @return Expr\Func
     */
    private function getAccountProductVisibilitySubQuery(QueryBuilder $queryBuilder, $websiteId)
    {
        $subQueryBuilder = $this->getVisibilityQueryBuilder(
            $queryBuilder,
            AccountProductVisibilityResolved::class,
            'account_product_visibility_resolved',
            $websiteId
        );

        $accountFallback = $this->addCategoryConfigFallback('account_product_visibility_resolved.visibility');

        $visibilityTerm = $this->getAccountProductVisibilityResolvedVisibilityTerm($accountFallback);

        $subQueryBuilder->andWhere(
            $subQueryBuilder->expr()->gt($visibilityTerm, 0)
        );

        return $queryBuilder->expr()->exists($subQueryBuilder->getDQL());
    }

    /**
     * @param string $accountFallback
     * @return string
     */
    private function getAccountProductVisibilityResolvedVisibilityTerm($accountFallback)
    {
        $term = <<<TERM
CASE WHEN account_product_visibility_resolved.visibility = %s
    THEN 0
ELSE (COALESCE(%s, 0) * 100)
END
TERM;
        return sprintf(
            $term,
            AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $accountFallback
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $websiteId
     * @return Expr\Func
     */
    private function getAccountGroupProductVisibilitySubQuery(QueryBuilder $queryBuilder, $websiteId)
    {
        $subQueryBuilder = $this->getVisibilityQueryBuilder(
            $queryBuilder,
            AccountGroupProductVisibilityResolved::class,
            'account_group_product_visibility_resolved',
            $websiteId
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
