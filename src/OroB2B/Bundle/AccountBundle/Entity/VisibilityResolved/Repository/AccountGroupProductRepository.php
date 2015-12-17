<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - accountGroup
 *  - website
 *  - product
 */
class AccountGroupProductRepository extends AbstractVisibilityRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param integer $cacheVisibility
     * @param integer[] $categories
     * @param integer $accountGroupId
     * @param Website|null $website
     */
    public function insertByCategory(
        InsertFromSelectQueryExecutor $insertFromSelect,
        $cacheVisibility,
        $categories,
        $accountGroupId,
        Website $website = null
    ) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select(
                'IDENTITY(agpv.website)',
                'product.id',
                (string)$accountGroupId,
                (string)$cacheVisibility,
                (string)BaseProductVisibilityResolved::SOURCE_CATEGORY,
                'category.id'
            )
            ->innerJoin('category.products', 'product')
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility',
                'agpv',
                Join::WITH,
                'agpv.product = product AND agpv.visibility = :category AND IDENTITY(agpv.accountGroup) = :accGroupId'
            )
            ->where('category.id in (:ids)');

        $queryBuilder
            ->setParameter('ids', $categories)
            ->setParameter('accGroupId', $accountGroupId)
            ->setParameter('category', AccountGroupProductVisibility::CATEGORY);

        if ($website) {
            $queryBuilder->andWhere('agpv.website = :website')
                ->setParameter('website', $website);
        }
        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'website',
                'product',
                'accountGroup',
                'visibility',
                'source',
                'categoryId',
            ],
            $queryBuilder
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Website|null $website
     */
    public function insertStatic(InsertFromSelectQueryExecutor $insertFromSelect, Website $website = null)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->createQueryBuilder('agpv')
            ->select(
                [
                    'IDENTITY(agpv.website)',
                    'IDENTITY(agpv.product)',
                    'IDENTITY(agpv.accountGroup)',
                    'CASE WHEN agpv.visibility = :visible THEN :cacheVisible ELSE :cacheHidden END',
                    (string)BaseProductVisibilityResolved::SOURCE_STATIC,
                ]
            )
            ->where('agpv.visibility = :visible OR agpv.visibility = :hidden')
            ->setParameter('visible', AccountGroupProductVisibility::VISIBLE)
            ->setParameter('hidden', AccountGroupProductVisibility::HIDDEN)
            ->setParameter('cacheVisible', BaseProductVisibilityResolved::VISIBILITY_VISIBLE)
            ->setParameter('cacheHidden', BaseProductVisibilityResolved::VISIBILITY_HIDDEN);

        if ($website) {
            $queryBuilder->andWhere('agpv.website = :website')
                ->setParameter('website', $website);
        }

        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'website',
                'product',
                'accountGroup',
                'visibility',
                'source',
            ],
            $queryBuilder
        );
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Product $product
     * @param Website $website
     * @return null|AccountGroupProductVisibilityResolved
     */
    public function findByPrimaryKey(AccountGroup $accountGroup, Product $product, Website $website)
    {
        return $this->findOneBy(['accountGroup' => $accountGroup, 'website' => $website, 'product' => $product]);
    }
}
