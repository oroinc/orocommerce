<?php

namespace Oro\Bundle\VisibilityBundle\Visibility;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityScopeProvider;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\Website\WebsiteInterface;

/**
 * Expands the ability to adjust the products visibilities.
 */
trait ProductVisibilityTrait
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
     * @var array
     */
    protected $configValue = [];

    /**
     * @var VisibilityScopeProvider
     */
    protected $visibilityScopeProvider;

    /**
     * @param QueryBuilder $queryBuilder
     * @param CustomerGroup $customerGroup
     * @param WebsiteInterface $website
     * @return string
     */
    private function getCustomerGroupProductVisibilityResolvedTermByWebsite(
        QueryBuilder $queryBuilder,
        CustomerGroup $customerGroup,
        WebsiteInterface $website
    ) {
        $fieldName = $this->getCustomerGroupProductVisibilityFieldNameResolvedByWebsite(
            $queryBuilder,
            $customerGroup,
            $website
        );

        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback($fieldName)
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param CustomerGroup $customerGroup
     * @param WebsiteInterface $website
     *
     * @return string
     */
    private function getCustomerGroupProductVisibilityFieldNameResolvedByWebsite(
        QueryBuilder $queryBuilder,
        CustomerGroup $customerGroup,
        WebsiteInterface $website
    ) {
        $queryBuilder->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved',
            'customer_group_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'customer_group_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq(
                    'customer_group_product_visibility_resolved.scope',
                    ':customerGroupScope'
                )
            )
        );

        $scope = $this->getVisibilityScopeProvider()->getCustomerGroupProductVisibilityScope($customerGroup, $website);

        $queryBuilder->setParameter('customerGroupScope', $scope);

        return 'customer_group_product_visibility_resolved.visibility';
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Customer $customer
     * @param WebsiteInterface $website
     * @return string
     */
    private function getCustomerProductVisibilityResolvedTermByWebsite(
        QueryBuilder $queryBuilder,
        Customer $customer,
        WebsiteInterface $website
    ) {
        $queryBuilder->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved',
            'customer_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'customer_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('customer_product_visibility_resolved.scope', ':customerScope')
            )
        );

        $scope = $this->getVisibilityScopeProvider()->getCustomerProductVisibilityScope($customer, $website);

        $queryBuilder->setParameter('customerScope', $scope);

        $productFallback = $this->addCategoryConfigFallback('product_visibility_resolved.visibility');
        $customerFallback = $this->addCategoryConfigFallback('customer_product_visibility_resolved.visibility');

        return $this->getCustomerProductVisibilityResolvedVisibilityTerm($productFallback, $customerFallback);
    }

    /**
     * @param string $productFallback
     * @param string $customerFallback
     * @return string
     */
    private function getCustomerProductVisibilityResolvedVisibilityTerm($productFallback, $customerFallback)
    {
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

    private function buildProductVisibilityResolvedTermByWebsiteConditions(
        QueryBuilder $queryBuilder,
        string $fieldName,
        int $defaultVisibility
    ): void {
        $callback = function ($visibility) use ($defaultVisibility) {
            $currentVisibility = $this->buildConfigFallback($visibility, $this->getProductConfigValue()) > 0
                ? BaseVisibilityResolved::VISIBILITY_VISIBLE
                : BaseVisibilityResolved::VISIBILITY_HIDDEN;

            return $currentVisibility === $defaultVisibility;
        };

        $visibilities = array_filter(ProductVisibilityProvider::VISIBILITIES, $callback);

        $this->buildVisibilityConditions($queryBuilder, $fieldName, $visibilities);
    }

    private function buildProductVisibilityResolvedTermByWebsite(
        QueryBuilder $queryBuilder,
        WebsiteInterface $website
    ): string {
        $queryBuilder->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved',
            'product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq($this->getRootAlias($queryBuilder), 'product_visibility_resolved.product'),
                $queryBuilder->expr()->eq('product_visibility_resolved.scope', ':scope')
            )
        );

        $queryBuilder->setParameter('scope', $this->getVisibilityScopeProvider()->getProductVisibilityScope($website));

        return 'product_visibility_resolved.visibility';
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param WebsiteInterface $website
     * @return string
     */
    private function getProductVisibilityResolvedTermByWebsite(QueryBuilder $queryBuilder, WebsiteInterface $website)
    {
        $fieldName = $this->buildProductVisibilityResolvedTermByWebsite($queryBuilder, $website);

        return sprintf(
            'COALESCE(%s, %s)',
            $this->addCategoryConfigFallback($fieldName),
            $this->getProductConfigValue()
        );
    }

    private function buildVisibilityConditions(
        QueryBuilder $queryBuilder,
        string $fieldName,
        array $visibilities
    ): void {
        $orExpr = [];
        QueryBuilderUtil::checkField($fieldName);
        foreach ($visibilities as $visibility) {
            QueryBuilderUtil::checkParameter($visibility);
            $orExpr[] = null === $visibility
                ? $queryBuilder->expr()->isNull($fieldName)
                : $queryBuilder->expr()->eq($fieldName, $visibility);
        }

        $queryBuilder->andWhere($queryBuilder->expr()->andX($queryBuilder->expr()->orX(...$orExpr)));
    }

    /**
     * @param string $field
     * @return string
     */
    protected function addCategoryConfigFallback($field)
    {
        QueryBuilderUtil::checkField($field);

        return sprintf(
            'CASE WHEN %1$s = %2$s THEN %3$s ELSE %1$s END',
            $field,
            BaseVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $this->getCategoryConfigValue()
        );
    }

    protected function buildConfigFallback(
        ?int $visibility,
        $nullValue = BaseVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
    ): int {
        return match ($visibility) {
            default => $visibility,
            null => $nullValue,
            BaseVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG => $this->getCategoryConfigValue()
        };
    }

    /**
     * @return int
     */
    protected function getProductConfigValue()
    {
        return (int)$this->getConfigValue($this->productConfigPath);
    }

    /**
     * @return int
     */
    protected function getCategoryConfigValue()
    {
        return (int)$this->getConfigValue($this->categoryConfigPath);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    protected function getRootAlias(QueryBuilder $queryBuilder)
    {
        $aliases = $queryBuilder->getRootAliases();

        return reset($aliases);
    }

    /**
     * @param string $path
     * @return integer
     * @throws \LogicException
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

    public function setVisibilityScopeProvider(VisibilityScopeProvider $visibilityScopeProvider)
    {
        $this->visibilityScopeProvider = $visibilityScopeProvider;
    }

    /**
     * @return VisibilityScopeProvider
     * @throws \RuntimeException
     */
    public function getVisibilityScopeProvider()
    {
        if (!$this->visibilityScopeProvider) {
            throw new \RuntimeException('Visibility scope provider was not set');
        }

        return $this->visibilityScopeProvider;
    }
}
