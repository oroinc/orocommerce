<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;

class RestrictProductsIndexEventListener
{
    /** @var ConfigManager  */
    private $configManager;

    /** @var string */
    private $productConfigPath;

    /** @var string */
    private $categoryConfigPath;

    /** @var array */
    private $configValue = [];

    /**
     * @param ConfigManager $configManager
     * @param string $productConfigPath
     * @param string $categoryConfigPath
     */
    public function __construct(ConfigManager $configManager, $productConfigPath, $categoryConfigPath)
    {
        $this->configManager = $configManager;
        $this->productConfigPath = $productConfigPath;
        $this->categoryConfigPath = $categoryConfigPath;
    }

    /**
     * @param RestrictIndexEntityEvent $event
     */
    public function onRestrictIndexEntityEvent(RestrictIndexEntityEvent $event)
    {
        return;
        $context = $event->getContext();

        if (!isset($context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY])) {
            throw new \LogicException('"%s" required', AbstractIndexer::CONTEXT_WEBSITE_ID_KEY);
        }

        $qb = $event->getQueryBuilder();

        $websiteId = $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY];

        $this->configureProductVisibility($qb, $websiteId);
        $this->configureAccountGroupProductVisibility($qb, $websiteId);
        $this->configureAccountProductVisibility($qb, $websiteId);
        $this->configureAccountVisibility($qb);

        $productVisibility = [];
        $productVisibility[] = $this->getProductVisibilityResolvedQueryPart($qb, $websiteId);
        $productVisibility[] = $this->getAccountGroupProductVisibilityResolvedQueryPart($qb, $websiteId);
        $productVisibility[] = $this->getAccountProductVisibilityResolvedQueryPart($qb, $websiteId);

        $qb
            ->distinct()
            ->andWhere($qb->expr()->gt(implode(' + ', $productVisibility), 0));
    }

    /**
     * @param QueryBuilder $qb
     * @param int $websiteId
     * @return string
     */
    protected function getProductVisibilityResolvedQueryPart(QueryBuilder $qb, $websiteId)
    {
        return sprintf(
            'COALESCE(%s, %s)',
            $this->addCategoryConfigFallback('product_visibility_resolved.visibility'),
            $this->getProductConfigValue()
        );
    }

    /**
     * @param QueryBuilder $qb
     * @param int $websiteId
     * @return string
     */
    protected function getAccountGroupProductVisibilityResolvedQueryPart(QueryBuilder $qb, $websiteId)
    {
        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('account_group_product_visibility_resolved.visibility')
        );
    }

    /**
     * @param QueryBuilder $qb
     * @param int $websiteId
     * @return string
     */
    protected function getAccountProductVisibilityResolvedQueryPart(QueryBuilder $qb, $websiteId)
    {
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

    /**
     * @param QueryBuilder $qb
     * @param int $websiteId
     */
    protected function configureProductVisibility(QueryBuilder $qb, $websiteId)
    {
        $qb->leftJoin(
            ProductVisibilityResolved::class,
            'product_visibility_resolved',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq($this->getRootAlias($qb), 'product_visibility_resolved.product'),
                $qb->expr()->eq('product_visibility_resolved.website', ':website')
            )
        );

        $qb->setParameter('website', $websiteId);
    }

    /**
     * @param QueryBuilder $qb
     * @param int $websiteId
     */
    protected function configureAccountGroupProductVisibility(QueryBuilder $qb, $websiteId)
    {
        $qb->leftJoin(
            AccountGroupProductVisibilityResolved::class,
            'account_group_product_visibility_resolved',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq(
                    $this->getRootAlias($qb),
                    'account_group_product_visibility_resolved.product'
                ),
                $qb->expr()->eq('account_group_product_visibility_resolved.website', ':website')
            )
        );

        $qb->setParameter('website', $websiteId);
    }

    /**
     * @param QueryBuilder $qb
     * @param int $websiteId
     */
    protected function configureAccountProductVisibility(QueryBuilder $qb, $websiteId)
    {
        $qb->leftJoin(
            AccountProductVisibilityResolved::class,
            'account_product_visibility_resolved',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq(
                    $this->getRootAlias($qb),
                    'account_product_visibility_resolved.product'
                ),
                $qb->expr()->eq('account_product_visibility_resolved.website', ':website')
            )
        );

        $qb->setParameter('website', $websiteId);
    }

    /**
     * @param QueryBuilder $qb
     */
    protected function configureAccountVisibility(QueryBuilder $qb)
    {
        $qb
            ->leftJoin(
                Account::class,
                'account',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq(
                        'account_group_product_visibility_resolved.accountGroup',
                        'account.group'
                    ),
                    $qb->expr()->eq('account_product_visibility_resolved.account', 'account')
                )
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
