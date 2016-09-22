<?php

namespace Oro\Bundle\AccountBundle\Visibility\Provider;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\AccountBundle\Visibility\ProductVisibilityTrait;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AccountProductVisibilityProvider
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
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * Returns fields to index with product.
     *
     * @param array $productIds
     * @param int $websiteId
     * @return array
     */
    public function getAccountVisibilitiesForProducts(array $productIds, $websiteId)
    {
        $qb = $this->createProductsQuery($productIds);

        $qb //Dummy condition to join not related entity
            ->join(Account::class, 'account', Join::WITH, 'account.id <> 0');

        $visibilityTerm = $this->getTotalAccountsProductVisibilityResolvedTerm($qb, $this->getWebsiteById($websiteId));

        $qb
            ->addSelect('account.id as accountId')
            ->andWhere($qb->expr()->neq($visibilityTerm, $this->getCategoryConfigValue()))
            ->addOrderBy('accountId', Query::ORDER_ASC);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param array $productIds
     * @param int $websiteId
     * @return array
     */
    public function getNewUserAndAnonymousVisibilitiesForProducts(array $productIds, $websiteId)
    {
        $qb = $this->createProductsQuery($productIds);

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
                'CASE WHEN %s > 0 THEN 1 ELSE 0 END as visibility_new',
                $productVisibilityTerm
            ))
            ->addSelect(sprintf(
                'CASE WHEN %s > 0 THEN 1 ELSE 0 END as visibility_anonymous',
                $anonymousGroupVisibilityTerm
            ));

        return $qb->getQuery()->getArrayResult();
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
     * @return AccountGroup
     */
    private function getAnonymousAccountGroup()
    {
        $anonymousGroupId = $this->configManager->get('oro_account.anonymous_account_group');

        return $this->doctrineHelper
            ->getEntityRepository(AccountGroup::class)
            ->find($anonymousGroupId);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Website $website
     * @return string
     */
    private function getTotalAccountsProductVisibilityResolvedTerm(QueryBuilder $queryBuilder, Website $website)
    {
        $productVisibilityTerms = [
            $this->getProductVisibilityResolvedTermByWebsite($queryBuilder, $website),
            $this->getAllAccountGroupsProductVisibilityResolvedTerm($queryBuilder, $website),
            $this->getAllAccountsProductVisibilityResolvedTerm($queryBuilder, $website)
        ];

        return sprintf(
            'CASE WHEN %s > 0 THEN %s ELSE %s END',
            implode('+', $productVisibilityTerms),
            BaseVisibilityResolved::VISIBILITY_VISIBLE,
            BaseVisibilityResolved::VISIBILITY_HIDDEN
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Website $website
     * @return string
     */
    private function getAllAccountsProductVisibilityResolvedTerm(QueryBuilder $queryBuilder, Website $website)
    {
        $queryBuilder->leftJoin(
            'Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved',
            'account_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('account_product_visibility_resolved.account', 'account'),
                $queryBuilder->expr()->eq('account_product_visibility_resolved.website', ':_website')
            )
        );

        $queryBuilder->setParameter('_website', $website);

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
     * @param Website $website
     * @return string
     */
    private function getAllAccountGroupsProductVisibilityResolvedTerm(QueryBuilder $queryBuilder, Website $website)
    {
        $queryBuilder->leftJoin(
            'Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved',
            'account_group_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_group_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq(
                    'account_group_product_visibility_resolved.accountGroup',
                    'account.group'
                ),
                $queryBuilder->expr()->eq('account_group_product_visibility_resolved.website', ':_website')
            )
        );

        $queryBuilder->setParameter('_website', $website);

        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('account_group_product_visibility_resolved.visibility')
        );
    }

    /**
     * @param array $productIds
     * @return QueryBuilder
     */
    private function createProductsQuery(array $productIds)
    {
        $qb = $this->doctrineHelper->getEntityManagerForClass(Product::class)->createQueryBuilder();

        $qb
            ->select('product.id as productId')
            ->from(Product::class, 'product')
            ->where($qb->expr()->in('product.id', $productIds))
            ->addOrderBy('productId', Query::ORDER_ASC);

        return $qb;
    }
}
