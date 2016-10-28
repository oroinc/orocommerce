<?php

namespace Oro\Bundle\CustomerBundle\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;

class RestrictProductsIndexEventListener
{
    use ContextTrait;

    /** @var ConfigManager  */
    private $configManager;

    /** @var string */
    private $productConfigPath;

    /** @var string */
    private $categoryConfigPath;

    /** @var array */
    private $configValue = [];

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

        $qb->andWhere(
            $qb->expr()->orX(
                $this->getProductVisibilitySubQuery($qb),
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
    private function getProductVisibilitySubQuery(QueryBuilder $queryBuilder)
    {
        $subQueryBuilder = $this->getVisibilityQueryBuilder(
            $queryBuilder,
            ProductVisibilityResolved::class,
            'product_visibility_resolved'
        );

        $subQueryBuilder->andWhere(
            $subQueryBuilder->expr()->gt($this->getProductVisibilityResolvedQueryPart(), 0)
        );

        return $queryBuilder->expr()->exists($subQueryBuilder->getDQL());
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

        $subQueryBuilder->andWhere(
            $subQueryBuilder->expr()->gt($this->getAccountProductVisibilityResolvedQueryPart(), 0)
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
    protected function getProductVisibilityResolvedQueryPart()
    {
        return sprintf(
            'COALESCE(%s, %s)',
            $this->addCategoryConfigFallback('product_visibility_resolved.visibility'),
            $this->getProductConfigValue()
        );
    }

    /**
     * @return string
     */
    protected function getAccountGroupProductVisibilityResolvedQueryPart()
    {
        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('account_group_product_visibility_resolved.visibility')
        );
    }

    /**
     * @return string
     */
    protected function getAccountProductVisibilityResolvedQueryPart()
    {
        $productFallback = $this->addCategoryConfigFallback('sub_query_product_visibility_resolved.visibility');
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

    /**
     * @param QueryBuilder $queryBuilder
     * @return mixed
     */
    protected function getRootAlias(QueryBuilder $queryBuilder)
    {
        return $queryBuilder->getRootAliases()[0];
    }

    /**
     * @param string $field
     * @return string
     */
    protected function addCategoryConfigFallback($field)
    {
        return sprintf(
            'CASE WHEN %1$s = %2$s THEN %3$s ELSE %1$s END',
            $field,
            BaseVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $this->getCategoryConfigValue()
        );
    }

    /**
     * @return int
     */
    protected function getCategoryConfigValue()
    {
        return $this->getConfigValue($this->categoryConfigPath);
    }

    /**
     * @return int
     */
    protected function getProductConfigValue()
    {
        return $this->getConfigValue($this->productConfigPath);
    }

    /**
     * @param string $path
     * @return int
     */
    protected function getConfigValue($path)
    {
        if (!empty($this->configValue[$path])) {
            return $this->configValue[$path];
        }

        if (!$this->productConfigPath) {
            throw new \LogicException(
                sprintf('%s::productConfigPath not configured', get_class($this))
            );
        }
        if (!$this->categoryConfigPath) {
            throw new \LogicException(
                sprintf('%s::categoryConfigPath not configured', get_class($this))
            );
        }

        $this->configValue = [
            $this->productConfigPath => $this->configManager->get($this->productConfigPath),
            $this->categoryConfigPath => $this->configManager->get($this->categoryConfigPath),
        ];

        foreach ($this->configValue as $key => $value) {
            $this->configValue[$key] = $value === VisibilityInterface::VISIBLE
                ? BaseVisibilityResolved::VISIBILITY_VISIBLE
                : BaseVisibilityResolved::VISIBILITY_HIDDEN;
        }

        return $this->configValue[$path];
    }
}
