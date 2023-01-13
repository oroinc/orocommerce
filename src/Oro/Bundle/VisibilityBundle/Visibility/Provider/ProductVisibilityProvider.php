<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\ProductVisibilityTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Visibility Provider for Product entity.
 */
class ProductVisibilityProvider
{
    use ProductVisibilityTrait;

    public const VISIBILITIES = [
        null, // Simulates the lack of relation between the product and the visibility configuration.
        BaseVisibilityResolved::VISIBILITY_HIDDEN,
        BaseVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
        BaseVisibilityResolved::VISIBILITY_VISIBLE,
        CustomerProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL
    ];

    // The query execution time depends not only on the complexity of the query and the size of data,
    // but also on the time to planning the query itself by sql engine.
    // Therefore, not always limiting the results in the query will speed up. In this case,
    // the difference in thousands of results per iteration will take much less time than planning
    // a query with different input parameters dozens of times in a row.
    //
    // Important: a large number of batches can lead to high memory usage, which can lead to errors
    // (see BufferedIdentityQueryResultIterator).
    private int $queryBufferSize = 5000;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    public function setQueryBufferSize(int $queryBufferSize): void
    {
        $this->queryBufferSize = $queryBufferSize;
    }

    /**
     * Returns fields to index with product.
     *
     * @param Product[]|array $products
     * @param int $websiteId
     * @return \Generator
     */
    public function getCustomerVisibilitiesForProducts(array $products, $websiteId)
    {
        $website = $this->getWebsiteById($websiteId);

        $productsWithCategoryConfigVisibility = $this->getProductsByDefaultVisibility(
            $this->getCategoryConfigValue(),
            $products,
            $website
        );

        yield from $this->getCustomersDataBasedOnCustomerGroupProductVisibility(
            $website,
            $productsWithCategoryConfigVisibility,
            $this->getCategoryConfigValue()
        );

        yield from $this->getCustomersDataBasedOnCustomerProductVisibility(
            $website,
            $productsWithCategoryConfigVisibility,
            $this->getCategoryConfigValue()
        );

        yield from $this->processInverseProducts($products, $website);
    }

    /**
     * @param array|Product[] $products
     * @param Website $website
     * @return \Generator
     */
    private function processInverseProducts(array $products, Website $website)
    {
        $productsWithCategoryConfigVisibility = $this->getProductsByDefaultVisibility(
            $this->getInverseCategoryConfigValue(),
            $products,
            $website
        );

        $knownHashes = [];
        $customersData = $this->getCustomersDataBasedOnCustomerGroupProductVisibility(
            $website,
            $productsWithCategoryConfigVisibility,
            $this->getInverseCategoryConfigValue()
        );
        foreach ($customersData as $dataRow) {
            $knownHashes[$this->getDataRowHash($dataRow)] = true;
        }

        $customersData = $this->getCustomersDataBasedOnCustomerProductVisibility(
            $website,
            $productsWithCategoryConfigVisibility,
            $this->getInverseCategoryConfigValue()
        );
        foreach ($customersData as $dataRow) {
            $knownHashes[$this->getDataRowHash($dataRow)] = true;
        }

        foreach ($this->getAllCustomersData($productsWithCategoryConfigVisibility) as $dataRow) {
            if (!array_key_exists($this->getDataRowHash($dataRow), $knownHashes)) {
                yield $dataRow;
            }
        }
    }

    private function getDataRowHash(array $dataRow): string
    {
        return $dataRow['productId'] . ':' . $dataRow['customerId'];
    }

    /**
     * @return BufferedIdentityQueryResultIterator
     */
    private function getCustomerIds()
    {
        $queryBuilder = $this->doctrineHelper->getEntityManagerForClass(Customer::class)->createQueryBuilder();
        $queryBuilder
            ->select('customer.id as customerIid')
            ->from(Customer::class, 'customer')
            ->orderBy('customer.id');

        $identifierHydrationMode = 'IdentifierHydrator';
        $query = $queryBuilder->getQuery();
        $query
            ->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode($identifierHydrationMode, IdentifierHydrator::class);

        $query->setHydrationMode($identifierHydrationMode);

        $buffer = new BufferedIdentityQueryResultIterator($query);
        $buffer->setBufferSize($this->queryBufferSize);

        return $buffer;
    }

    /**
     * @param array $productIds
     * @return \Generator
     */
    private function getAllCustomersData(array $productIds)
    {
        foreach ($this->getCustomerIds() as $customerId) {
            foreach ($productIds as $productId) {
                yield ['productId' => $productId, 'customerId' => $customerId];
            }
        }
    }

    /**
     * @param Product[] $products
     * @param int $websiteId
     * @return array
     */
    public function getNewUserAndAnonymousVisibilitiesForProducts(array $products, $websiteId)
    {
        $qb = $this->createProductsQuery($products);

        $productConfigValue = $this->getProductConfigValue();
        $categoryConfigValue = $this->getCategoryConfigValue();

        $productVisibilityTerm = $this->buildProductVisibilityResolvedTermByWebsite(
            $qb,
            $this->getWebsiteById($websiteId)
        );

        $anonymousGroupVisibilityTerm = $this->getCustomerGroupProductVisibilityFieldNameResolvedByWebsite(
            $qb,
            $this->getAnonymousCustomerGroup(),
            $this->getWebsiteById($websiteId),
        );

        $qb
            ->addSelect(sprintf('%s as visibility_new', $productVisibilityTerm))
            ->addSelect(sprintf('%s as visibility_anonymous', $anonymousGroupVisibilityTerm));

        $visibilities = $qb->getQuery()->getArrayResult();

        foreach ($visibilities as &$visibility) {
            $visibility['visibility_new'] = $visibility['visibility_new'] === 0
                ? $categoryConfigValue
                : $visibility['visibility_new'];

            $visibilityNew = $visibility['visibility_new'] = $visibility['visibility_new'] === null
                ? $productConfigValue
                : $visibility['visibility_new'];

            $visibility['visibility_new'] = $visibility['visibility_new'] > 0
                ? BaseVisibilityResolved::VISIBILITY_VISIBLE
                : BaseVisibilityResolved::VISIBILITY_HIDDEN;

            $visibility['visibility_anonymous'] = $visibility['visibility_anonymous'] === 0
                ? $categoryConfigValue
                : $visibility['visibility_anonymous'];

            $visibility['visibility_anonymous'] = $visibility['visibility_anonymous'] === null
                ? 0
                : $visibility['visibility_anonymous'];

            $visibility['visibility_anonymous'] = ($visibilityNew + $visibility['visibility_anonymous'] * 10) > 0
                ? BaseVisibilityResolved::VISIBILITY_VISIBLE
                : BaseVisibilityResolved::VISIBILITY_HIDDEN;


            $visibility['is_visible_by_default'] = $categoryConfigValue;
        }

        return $visibilities;
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @return QueryBuilder
     */
    public function getCustomerProductsVisibilitiesByWebsiteQueryBuilder(Customer $customer, Website $website)
    {
        $queryBuilder = $this->doctrineHelper->getEntityManagerForClass(Product::class)->createQueryBuilder();

        $queryBuilder->from(Product::class, 'product');

        $visibilities = [
            $this->getProductVisibilityResolvedTermByWebsite($queryBuilder, $website),
            $this->getCustomerProductVisibilityResolvedTermByWebsite($queryBuilder, $customer, $website)
        ];

        $customerGroup = $customer->getGroup();
        if ($customerGroup) {
            $visibilities[] = $this->getCustomerGroupProductVisibilityResolvedTermByWebsite(
                $queryBuilder,
                $customerGroup,
                $website
            );
        }

        $visibilityCondition = $this->getVisibilityConditionForVisibilityTerms($visibilities);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->neq($visibilityCondition, ':productVisibilityCategoryConfigValue'))
            ->setParameter('productVisibilityCategoryConfigValue', $this->getCategoryConfigValue())
            ->addOrderBy('product.id', Query::ORDER_ASC);

        return $queryBuilder;
    }

    /**
     * @param int $websiteId
     * @return Website
     */
    private function getWebsiteById($websiteId)
    {
        return $this->doctrineHelper
            ->getEntityRepository(Website::class)
            ->find($websiteId);
    }

    /**
     * @return CustomerGroup
     */
    private function getAnonymousCustomerGroup()
    {
        $anonymousGroupId = $this->configManager->get('oro_customer.anonymous_customer_group');

        return $this->doctrineHelper
            ->getEntityRepository(CustomerGroup::class)
            ->find($anonymousGroupId);
    }

    /**
     * @param array $visibilityTerms
     * @return string
     */
    private function getVisibilityConditionForVisibilityTerms(array $visibilityTerms)
    {
        return sprintf(
            'CASE WHEN %s > 0 THEN %s ELSE %s END',
            implode('+', $visibilityTerms),
            BaseVisibilityResolved::VISIBILITY_VISIBLE,
            BaseVisibilityResolved::VISIBILITY_HIDDEN
        );
    }

    private function buildAllCustomerGroupsProductVisibilityResolvedTermRestrictions(
        QueryBuilder $queryBuilder,
        string $fieldName,
        int $defaultVisibility
    ): void {
        $callback = function ($visibility) use ($defaultVisibility) {
            $currentVisibility = $this->buildConfigFallback($visibility) > 0
                ? BaseVisibilityResolved::VISIBILITY_VISIBLE
                : BaseVisibilityResolved::VISIBILITY_HIDDEN;

            return $defaultVisibility === $currentVisibility;
        };

        $visibilities = array_filter(self::VISIBILITIES, $callback);
        $this->buildVisibilityConditions($queryBuilder, $fieldName, $visibilities);
    }

    private function buildAllCustomerGroupsProductVisibilityResolvedTerm(
        QueryBuilder $queryBuilder,
        Website $website
    ): string {
        $customerProductVisibilitySubquery = $this->doctrineHelper
            ->getEntityManagerForClass(CustomerProductVisibilityResolved::class)
            ->createQueryBuilder();

        $customerProductVisibilitySubquery
            ->select('IDENTITY(customerProductVisibilityScope.customer)')
            ->from(CustomerProductVisibilityResolved::class, 'customerProductVisibilityResolved')
            ->innerJoin(
                'customerProductVisibilityResolved.scope',
                'customerProductVisibilityScope',
                Join::WITH,
                $customerProductVisibilitySubquery->expr()->orX(
                    $customerProductVisibilitySubquery->expr()->isNull('customerProductVisibilityScope.website'),
                    $customerProductVisibilitySubquery
                        ->expr()
                        ->eq('customerProductVisibilityScope.website', ':_website')
                )
            )
            ->where(
                $customerProductVisibilitySubquery->expr()->andX(
                    $customerProductVisibilitySubquery->expr()->eq(
                        'customerProductVisibilityResolved.product',
                        'customer_group_product_visibility_resolved.product'
                    ),
                    $customerProductVisibilitySubquery
                        ->expr()
                        ->eq('customerProductVisibilityScope.customer', 'customer')
                )
            );

        $queryBuilder
            ->innerJoin(
                'OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved',
                'customer_group_product_visibility_resolved',
                Join::WITH,
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'customer_group_product_visibility_resolved.product'
                )
            )
            ->innerJoin(
                'customer_group_product_visibility_resolved.scope',
                'customerGroupScope',
                Join::WITH,
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('customerGroupScope.website', ':_website'),
                    $queryBuilder->expr()->isNull('customerGroupScope.website')
                )
            )
            ->innerJoin(
                'OroCustomerBundle:Customer',
                'customer',
                Join::WITH,
                'customer.group = customerGroupScope.customerGroup'
            )
            ->andWhere($queryBuilder->expr()->not(
                $queryBuilder->expr()->exists($customerProductVisibilitySubquery->getDQL())
            ));

        $queryBuilder->setParameter('_website', $website);

        return 'customer_group_product_visibility_resolved.visibility';
    }

    /**
     * @param Product[]|array $products
     * @return QueryBuilder
     */
    private function createProductsQuery(array $products)
    {
        $qb = $this->doctrineHelper->getEntityManagerForClass(Product::class)->createQueryBuilder();

        $qb
            ->select('product.id as productId')
            ->from(Product::class, 'product')
            ->where($qb->expr()->in('product', ':products'))
            ->setParameter('products', $products)
            ->addOrderBy('productId', Query::ORDER_ASC);

        return $qb;
    }

    private function buildCustomersDataBasedOnCustomerProductVisibilityRestrictions(
        QueryBuilder $queryBuilder,
        string $fieldName,
        int $productVisibility,
        int $defaultVisibility
    ): void {
        $visibilities = array_filter(
            self::VISIBILITIES,
            function ($visibility) use ($productVisibility, $defaultVisibility) {
                $currentVisibility = $visibility === CustomerProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL
                    ? $productVisibility
                    : $this->buildConfigFallback($visibility);

                $currentVisibility = $currentVisibility > 0
                    ? BaseVisibilityResolved::VISIBILITY_VISIBLE
                    : BaseVisibilityResolved::VISIBILITY_HIDDEN;

                return $defaultVisibility === $currentVisibility;
            }
        );

        $this->buildVisibilityConditions($queryBuilder, $fieldName, $visibilities);
    }

    /**
     * @param Website $website
     * @param array $products
     * @param int $productVisibility
     *
     * @return BufferedIdentityQueryResultIterator
     */
    private function getCustomersDataBasedOnCustomerProductVisibility(
        Website $website,
        $products,
        $productVisibility
    ) {
        $queryBuilder = $this->createProductsQuery($products);

        $queryBuilder
            ->innerJoin(
                'OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved',
                'customer_product_visibility_resolved',
                Join::WITH,
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'customer_product_visibility_resolved.product'
                )
            )
            ->innerJoin(
                'customer_product_visibility_resolved.scope',
                'scope',
                Join::WITH,
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('scope.website', ':customersWebsite'),
                    $queryBuilder->expr()->isNull('scope.website')
                )
            );

        $queryBuilder->setParameter('customersWebsite', $website);

        $this->buildCustomersDataBasedOnCustomerProductVisibilityRestrictions(
            $queryBuilder,
            'customer_product_visibility_resolved.visibility',
            $productVisibility,
            $this->inverseVisibility($productVisibility),
        );

        $queryBuilder
            ->select('product.id as productId, IDENTITY(scope.customer) as customerId')
            ->resetDQLPart('orderBy');

        $buffer = new BufferedIdentityQueryResultIterator($queryBuilder);
        $buffer->setBufferSize($this->queryBufferSize);

        return $buffer;
    }

    /**
     * @param Website $website
     * @param array $products
     * @param int $productVisibility
     *
     * @return BufferedIdentityQueryResultIterator
     */
    private function getCustomersDataBasedOnCustomerGroupProductVisibility(
        Website $website,
        $products,
        $productVisibility
    ) {
        $queryBuilder = $this->createProductsQuery($products);
        $fieldName = $this->buildAllCustomerGroupsProductVisibilityResolvedTerm($queryBuilder, $website);
        $this->buildAllCustomerGroupsProductVisibilityResolvedTermRestrictions(
            $queryBuilder,
            $fieldName,
            $this->inverseVisibility($productVisibility)
        );

        $queryBuilder
            ->select('product.id as productId, customer.id as customerId')
            ->resetDQLPart('orderBy');

        $buffer = new BufferedIdentityQueryResultIterator($queryBuilder);
        $buffer->setBufferSize($this->queryBufferSize);

        return $buffer;
    }

    /**
     * Returns products from $products for given $website which visibility equals to $defaultVisibility.
     *
     * @param int $defaultVisibility
     * @param array|Product[] $products
     * @param Website $website
     * @return array
     */
    private function getProductsByDefaultVisibility($defaultVisibility, $products, Website $website)
    {
        $queryBuilder = $this->createProductsQuery($products);
        $fieldName = $this->buildProductVisibilityResolvedTermByWebsite($queryBuilder, $website);
        $this->buildProductVisibilityResolvedTermByWebsiteConditions($queryBuilder, $fieldName, $defaultVisibility);

        $queryBuilder
            ->select('product.id as productId')
            ->addOrderBy('product.id');

        $identifierHydrationMode = 'IdentifierHydrator';
        $query = $queryBuilder->getQuery();
        $query
            ->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode($identifierHydrationMode, IdentifierHydrator::class);

        return $query->getResult($identifierHydrationMode);
    }

    /**
     * @return int
     */
    private function getInverseCategoryConfigValue()
    {
        return $this->inverseVisibility($this->getCategoryConfigValue());
    }

    /**
     * @param int $visibility
     * @return int
     */
    private function inverseVisibility($visibility)
    {
        return $visibility === BaseVisibilityResolved::VISIBILITY_VISIBLE
            ? BaseVisibilityResolved::VISIBILITY_HIDDEN
            : BaseVisibilityResolved::VISIBILITY_VISIBLE;
    }
}
