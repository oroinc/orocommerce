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
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;

class RestrictedProductsIndexEventListener
{
    const ACCOUNT_ALIAS = 'account';

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
        $context = $event->getContext();
        $queryBuilder = $event->getQueryBuilder();

        if (!$websiteId = $context['website_id']) {
            return;
        }

        $this->setAccountQueryPart($queryBuilder);

        $productVisibility = [$this->getProductVisibilityResolvedQueryPart($queryBuilder, $websiteId)];
        $productVisibility[] = $this->getAccountGroupProductVisibilityResolvedQueryPart($queryBuilder, $websiteId);
        $productVisibility[] = $this->getAccountProductVisibilityResolvedQueryPart($queryBuilder, $websiteId);

        $queryBuilder->andWhere($queryBuilder->expr()->gt(implode(' + ', $productVisibility), 0));
        $queryBuilder->distinct();
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    protected function setAccountQueryPart(QueryBuilder $queryBuilder)
    {
        $queryBuilder->join(
            Account::class,
            self::ACCOUNT_ALIAS,
            Join::WITH,
            $queryBuilder->expr()->neq(self::ACCOUNT_ALIAS, 0)
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $websiteId
     * @return string
     */
    protected function getProductVisibilityResolvedQueryPart(QueryBuilder $queryBuilder, $websiteId)
    {
        $queryBuilder->leftJoin(
            ProductVisibilityResolved::class,
            'product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq($this->getRootAlias($queryBuilder), 'product_visibility_resolved.product'),
                $queryBuilder->expr()->eq('product_visibility_resolved.website', ':website')
            )
        );

        $queryBuilder->setParameter('website', $websiteId);

        return sprintf(
            'COALESCE(%s, %s)',
            $this->addCategoryConfigFallback('product_visibility_resolved.visibility'),
            $this->getProductConfigValue()
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $websiteId
     * @return string
     */
    protected function getAccountGroupProductVisibilityResolvedQueryPart(QueryBuilder $queryBuilder, $websiteId)
    {
        $queryBuilder->leftJoin(
            AccountGroupProductVisibilityResolved::class,
            'account_group_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_group_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('account_group_product_visibility_resolved.website', ':_website'),
                $queryBuilder->expr()->eq(
                    'account_group_product_visibility_resolved.accountGroup',
                    self::ACCOUNT_ALIAS . '.group'
                )
            )
        );

        $queryBuilder->setParameter('_website', $websiteId);

        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('account_group_product_visibility_resolved.visibility')
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $websiteId
     * @return string
     */
    protected function getAccountProductVisibilityResolvedQueryPart(QueryBuilder $queryBuilder, $websiteId)
    {
        $queryBuilder->leftJoin(
            AccountProductVisibilityResolved::class,
            'account_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('account_product_visibility_resolved.account', self::ACCOUNT_ALIAS),
                $queryBuilder->expr()->eq('account_product_visibility_resolved.website', ':_website')
            )
        );

        $queryBuilder->setParameter('_website', $websiteId);

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
     * @return integer
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
