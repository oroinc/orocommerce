<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class ProductVisibilityQueryBuilderModifier
{
    /**
     * @var string
     */
    protected $productConfigPath;

    /**
     * @var string
     */
    protected $categoryConfigPath;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var array
     */
    protected $configValue = [];

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
     * @param string $path
     */
    public function setProductVisibilitySystemConfigurationPath($path)
    {
        $this->productConfigPath = $path;
    }

    /**
     * @param string $path
     */
    public function setCategoryVisibilitySystemConfigurationPath($path)
    {
        $this->categoryConfigPath = $path;
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
        $scope = $this->scopeManager->find('product_visibility');
        if (!$scope) {
            return '0';
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
        $scope = $this->scopeManager->find('account_group_product_visibility');
        if (!$scope) {
            return '0';
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
                $queryBuilder->expr()->eq('account_group_product_visibility_resolved.accountGroup', ':scope_group')
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
            return '0';
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

    /**
     * @return int
     */
    protected function getProductConfigValue()
    {
        return $this->getConfigValue($this->productConfigPath);
    }

    /**
     * @return int
     */
    protected function getCategoryConfigValue()
    {
        return $this->getConfigValue($this->categoryConfigPath);
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
     * @param ScopeManager $scopeManager
     */
    public function setScopeManager(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }
}
