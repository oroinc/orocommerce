<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
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
     * @return BufferedQueryResultIterator
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

        return new BufferedQueryResultIterator($query);
    }

    /**
     * @param array $productIds
     * @return \Generator
     */
    private function getAllCustomersData(array $productIds)
    {
        foreach ($productIds as $productId) {
            foreach ($this->getCustomerIds() as $customerId) {
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

        $productVisibilityTerm = $this->getProductVisibilityResolvedTermByWebsite(
            $qb,
            $this->getWebsiteById($websiteId)
        );

        $anonymousGroupVisibilityTerm = implode('+', [
            $productVisibilityTerm,
            $this->getCustomerGroupProductVisibilityResolvedTermByWebsite(
                $qb,
                $this->getAnonymousCustomerGroup(),
                $this->getWebsiteById($websiteId)
            )
        ]);

        $qb
            ->addSelect(sprintf(
                'CASE WHEN %s > 0 THEN %d ELSE %d END as visibility_new',
                $productVisibilityTerm,
                BaseVisibilityResolved::VISIBILITY_VISIBLE,
                BaseVisibilityResolved::VISIBILITY_HIDDEN
            ))
            ->addSelect(sprintf(
                'CASE WHEN %s > 0 THEN %d ELSE %d END as visibility_anonymous',
                $anonymousGroupVisibilityTerm,
                BaseVisibilityResolved::VISIBILITY_VISIBLE,
                BaseVisibilityResolved::VISIBILITY_HIDDEN
            ));

        $visibilities = $qb->getQuery()->getArrayResult();

        foreach ($visibilities as &$visibility) {
            $visibility['is_visible_by_default'] = $this->getCategoryConfigValue();
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

    /**
     * @param QueryBuilder $queryBuilder
     * @param Website $website
     * @return string
     */
    private function getAllCustomerGroupsProductVisibilityResolvedTerm(
        QueryBuilder $queryBuilder,
        Website $website
    ) {
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

        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('customer_group_product_visibility_resolved.visibility')
        );
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

    /**
     * @param int $fallbackProductVisibility
     * @return string
     */
    private function getCustomerProductVisibilityResolvedTerm($fallbackProductVisibility)
    {
        $customerFallback = $this->addCategoryConfigFallback('customer_product_visibility_resolved.visibility');

        return $this->getCustomerProductVisibilityResolvedVisibilityTerm($fallbackProductVisibility, $customerFallback);
    }

    /**
     * @param Website $website
     * @param array $products
     * @param int $productVisibility
     * @return BufferedQueryResultIterator
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

        $customerVisibilityTerm = $this->getCustomerProductVisibilityResolvedTerm($productVisibility);
        $customerVisibilityCondition = $this->getVisibilityConditionForVisibilityTerms([$customerVisibilityTerm]);

        $queryBuilder
            ->select('product.id as productId, IDENTITY(scope.customer) as customerId')
            ->andWhere(
                $queryBuilder->expr()->eq($customerVisibilityCondition, ':invertedProductVisibility')
            )
            ->setParameter('invertedProductVisibility', $this->inverseVisibility($productVisibility));

        return new BufferedQueryResultIterator($queryBuilder);
    }

    /**
     * @param Website $website
     * @param array $products
     * @param int $productVisibility
     * @return BufferedQueryResultIterator
     */
    private function getCustomersDataBasedOnCustomerGroupProductVisibility(
        Website $website,
        $products,
        $productVisibility
    ) {
        $queryBuilder = $this->createProductsQuery($products);

        $customerGroupVisibilityTerm = $this->getAllCustomerGroupsProductVisibilityResolvedTerm(
            $queryBuilder,
            $website
        );
        $customerGroupVisibilityCondition = $this->getVisibilityConditionForVisibilityTerms([
            $customerGroupVisibilityTerm
        ]);

        $queryBuilder
            ->select('product.id as productId, customer.id as customerId')
            ->andWhere(
                $queryBuilder->expr()->eq(
                    $customerGroupVisibilityCondition,
                    ':invertedProductVisibility'
                )
            )
            ->setParameter('invertedProductVisibility', $this->inverseVisibility($productVisibility))
            ->addOrderBy('customer.id', 'ASC');

        return new BufferedQueryResultIterator($queryBuilder);
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

        $productVisibilityTerm = $this->getProductVisibilityResolvedTermByWebsite($queryBuilder, $website);
        $productVisibilityCondition = $this->getVisibilityConditionForVisibilityTerms([$productVisibilityTerm]);

        $queryBuilder
            ->select('product.id as productId')
            ->andWhere($queryBuilder->expr()->eq($productVisibilityCondition, ':defaultVisibility'))
            ->setParameter('defaultVisibility', $defaultVisibility)
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
