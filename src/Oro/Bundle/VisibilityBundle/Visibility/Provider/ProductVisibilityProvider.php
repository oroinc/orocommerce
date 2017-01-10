<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Provider;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\VisibilityBundle\Visibility\ProductVisibilityTrait;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class ProductVisibilityProvider
{
    use ProductVisibilityTrait;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
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
     * @param Product[] $products
     * @param int $websiteId
     * @return array
     */
    public function getAccountVisibilitiesForProducts(array $products, $websiteId)
    {
        $website = $this->getWebsiteById($websiteId);

        $productsWithCategoryConfigVisibility = $this->getProductsByDefaultVisibility(
            $this->getCategoryConfigValue(),
            $products,
            $website
        );

        $accountsData = $this->getAccountsDataBasedOnAccountGroupProductVisibility(
            $website,
            $productsWithCategoryConfigVisibility,
            $this->getCategoryConfigValue()
        );

        $accountsData = array_merge(
            $accountsData,
            $this->getAccountsDataBasedOnAccountProductVisibility(
                $website,
                $productsWithCategoryConfigVisibility,
                $this->getCategoryConfigValue()
            )
        );

        $accountsData = array_merge($accountsData, $this->processInverseProducts($products, $website));

        usort($accountsData, [$this, 'compare']);

        return $accountsData;
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    private function compare(array $a, array $b)
    {
        if ($a['productId'] === $b['productId']) {
            return $a['accountId'] - $b['accountId'];
        }

        return $a['productId'] - $b['productId'];
    }

    /**
     * @param array $products
     * @param Website $website
     * @return array
     */
    private function processInverseProducts(array $products, Website $website)
    {
        $productsWithCategoryConfigVisibility = $this->getProductsByDefaultVisibility(
            $this->getInverseCategoryConfigValue(),
            $products,
            $website
        );

        $accountsData = $this->getAccountsDataBasedOnAccountGroupProductVisibility(
            $website,
            $productsWithCategoryConfigVisibility,
            $this->getInverseCategoryConfigValue()
        );

        $accountsData = array_merge(
            $accountsData,
            $this->getAccountsDataBasedOnAccountProductVisibility(
                $website,
                $productsWithCategoryConfigVisibility,
                $this->getInverseCategoryConfigValue()
            )
        );

        return array_udiff(
            $this->getAllAccountsData($productsWithCategoryConfigVisibility),
            $accountsData,
            [$this, 'compare']
        );
    }

    /**
     * @return array
     */
    private function getAccountIds()
    {
        $queryBuilder = $this->doctrineHelper->getEntityManagerForClass(Customer::class)->createQueryBuilder();
        $queryBuilder
            ->select('account.id as accountIid')
            ->from(Customer::class, 'account');

        return array_column($queryBuilder->getQuery()->getArrayResult(), 'accountIid');
    }

    /**
     * @param array $productIds
     * @return array
     */
    private function getAllAccountsData(array $productIds)
    {
        $data = [];
        foreach ($productIds as $productId) {
            foreach ($this->getAccountIds() as $accountId) {
                $data[] = ['productId' => $productId, 'accountId' => $accountId];
            }
        }

        return $data;
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
            $this->getAccountGroupProductVisibilityResolvedTermByWebsite(
                $qb,
                $this->getAnonymousAccountGroup(),
                $this->getWebsiteById($websiteId)
            )
        ]);

        $qb
            ->addSelect(sprintf(
                'CASE WHEN %s > 0 THEN %s ELSE %s END as visibility_new',
                $productVisibilityTerm,
                BaseVisibilityResolved::VISIBILITY_VISIBLE,
                BaseVisibilityResolved::VISIBILITY_HIDDEN
            ))
            ->addSelect(sprintf(
                'CASE WHEN %s > 0 THEN %s ELSE %s END as visibility_anonymous',
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
     * @param Customer $account
     * @param Website $website
     * @return QueryBuilder
     */
    public function getAccountProductsVisibilitiesByWebsiteQueryBuilder(Customer $account, Website $website)
    {
        $queryBuilder = $this->doctrineHelper->getEntityManagerForClass(Product::class)->createQueryBuilder();

        $queryBuilder->from(Product::class, 'product');

        $visibilities = [
            $this->getProductVisibilityResolvedTermByWebsite($queryBuilder, $website),
            $this->getAccountProductVisibilityResolvedTermByWebsite($queryBuilder, $account, $website)
        ];

        $accountGroup = $account->getGroup();
        if ($accountGroup) {
            $visibilities[] = $this->getAccountGroupProductVisibilityResolvedTermByWebsite(
                $queryBuilder,
                $accountGroup,
                $website
            );
        }

        $visibilityCondition = $this->getVisibilityConditionForVisibilityTerms($visibilities);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->neq($visibilityCondition, $this->getCategoryConfigValue()))
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
    private function getAnonymousAccountGroup()
    {
        $anonymousGroupId = $this->configManager->get('oro_customer.anonymous_account_group');

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
    private function getAllAccountGroupsProductVisibilityResolvedTerm(
        QueryBuilder $queryBuilder,
        Website $website
    ) {
        $accountProductVisibilitySubquery = $this->doctrineHelper
            ->getEntityManagerForClass(AccountProductVisibilityResolved::class)
            ->createQueryBuilder();

        $accountProductVisibilitySubquery
            ->select('IDENTITY(accountProductVisibilityScope.account)')
            ->from(AccountProductVisibilityResolved::class, 'accountProductVisibilityResolved')
            ->innerJoin(
                'accountProductVisibilityResolved.scope',
                'accountProductVisibilityScope',
                Join::WITH,
                $accountProductVisibilitySubquery->expr()->orX(
                    $accountProductVisibilitySubquery->expr()->isNull('accountProductVisibilityScope.website'),
                    $accountProductVisibilitySubquery->expr()->eq('accountProductVisibilityScope.website', ':_website')
                )
            )
            ->where(
                $accountProductVisibilitySubquery->expr()->andX(
                    $accountProductVisibilitySubquery->expr()->eq(
                        'accountProductVisibilityResolved.product',
                        'account_group_product_visibility_resolved.product'
                    ),
                    $accountProductVisibilitySubquery->expr()->eq('accountProductVisibilityScope.account', 'account')
                )
            );

        $queryBuilder
            ->innerJoin(
                'OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved',
                'account_group_product_visibility_resolved',
                Join::WITH,
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_group_product_visibility_resolved.product'
                )
            )
            ->innerJoin(
                'account_group_product_visibility_resolved.scope',
                'accountGroupScope',
                Join::WITH,
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('accountGroupScope.website', ':_website'),
                    $queryBuilder->expr()->isNull('accountGroupScope.website')
                )
            )
            ->innerJoin(
                'OroCustomerBundle:Customer',
                'account',
                Join::WITH,
                'account.group = accountGroupScope.accountGroup'
            )
            ->andWhere($queryBuilder->expr()->not(
                $queryBuilder->expr()->exists($accountProductVisibilitySubquery->getDQL())
            ));

        $queryBuilder->setParameter('_website', $website);

        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('account_group_product_visibility_resolved.visibility')
        );
    }

    /**
     * @param Product[] $products
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
    private function getAccountProductVisibilityResolvedTerm($fallbackProductVisibility)
    {
        $accountFallback = $this->addCategoryConfigFallback('account_product_visibility_resolved.visibility');

        return $this->getAccountProductVisibilityResolvedVisibilityTerm($fallbackProductVisibility, $accountFallback);
    }

    /**
     * @param Website $website
     * @param array $products
     * @param int $productVisibility
     * @return array
     */
    private function getAccountsDataBasedOnAccountProductVisibility(
        Website $website,
        $products,
        $productVisibility
    ) {
        $queryBuilder = $this->createProductsQuery($products);

        $queryBuilder
            ->innerJoin(
                'OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved',
                'account_product_visibility_resolved',
                Join::WITH,
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_product_visibility_resolved.product'
                )
            )
            ->innerJoin(
                'account_product_visibility_resolved.scope',
                'scope',
                Join::WITH,
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('scope.website', ':accountsWebsite'),
                    $queryBuilder->expr()->isNull('scope.website')
                )
            );

        $queryBuilder->setParameter('accountsWebsite', $website);

        $accountVisibilityTerm = $this->getAccountProductVisibilityResolvedTerm($productVisibility);
        $accountVisibilityCondition = $this->getVisibilityConditionForVisibilityTerms([$accountVisibilityTerm]);

        $queryBuilder
            ->select('product.id as productId, IDENTITY(scope.account) as accountId')
            ->andWhere(
                $queryBuilder->expr()->eq($accountVisibilityCondition, $this->inverseVisibility($productVisibility))
            );

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param Website $website
     * @param array $products
     * @param int $productVisibility
     * @return array
     */
    private function getAccountsDataBasedOnAccountGroupProductVisibility(
        Website $website,
        $products,
        $productVisibility
    ) {
        $queryBuilder = $this->createProductsQuery($products);

        $accountGroupVisibilityTerm = $this->getAllAccountGroupsProductVisibilityResolvedTerm(
            $queryBuilder,
            $website
        );
        $accountGroupVisibilityCondition = $this->getVisibilityConditionForVisibilityTerms([
            $accountGroupVisibilityTerm
        ]);

        $queryBuilder
            ->select('product.id as productId, account.id as accountId')
            ->andWhere(
                $queryBuilder->expr()->eq(
                    $accountGroupVisibilityCondition,
                    $this->inverseVisibility($productVisibility)
                )
            )
            ->addOrderBy('account.id', 'ASC');

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * Returns products from $products for given $website which visibility equals to $defaultVisibility.
     *
     * @param $defaultVisibility
     * @param $products
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
            ->andWhere($queryBuilder->expr()->eq($productVisibilityCondition, $defaultVisibility))
            ->addOrderBy('product.id');

        $productsResult = $queryBuilder->getQuery()->getArrayResult();

        return array_column($productsResult, 'productId');
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
