<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\BasicOperationRepositoryTrait;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - website
 *  - product
 */
class ProductVisibilityResolvedRepository extends EntityRepository
{
    use BasicOperationRepositoryTrait;

    /**
     * @param InsertFromSelectQueryExecutor $executor
     * @param int $visibility
     * @param Website $website
     * @param array $categories
     */
    public function insertByCategory(
        InsertFromSelectQueryExecutor $executor,
        $visibility,
        array $categories,
        Website $website = null
    ) {
        if (!$categories) {
            return;
        }

        $qb = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category');

        // DQL requires condition to be presented in join, "1 = 1" is used as a dummy condition
        $websiteJoinCondition = '1 = 1';
        if ($website) {
            $websiteJoinCondition = 'website.id = :websiteId';
            $qb->setParameter('websiteId', $website->getId());
        }

        $qb->select([
                'website.id as w_id',
                'product.id as p_id',
                (string)$visibility,
                (string)BaseProductVisibilityResolved::SOURCE_CATEGORY,
                'category.id as c_id',
            ])
            ->innerJoin('category.products', 'product')
            ->innerJoin('OroB2BWebsiteBundle:Website', 'website', Join::WITH, $websiteJoinCondition)
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\ProductVisibility',
                'pv',
                Join::WITH,
                'IDENTITY(pv.product) = product.id AND IDENTITY(pv.website) = website.id'
            )
            ->where('pv.id is null')
            ->andWhere('category.id in (:ids)')
            ->setParameter('ids', $categories);

        $executor->execute(
            $this->getClassName(),
            ['website', 'product', 'visibility', 'source', 'category'],
            $qb
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $executor
     * @param Website|null $website
     */
    public function insertFromBaseTable(InsertFromSelectQueryExecutor $executor, Website $website = null)
    {
        $visibilityCondition = sprintf(
            "CASE WHEN pv.visibility = '%s' THEN %s ELSE %s END",
            ProductVisibility::VISIBLE,
            ProductVisibilityResolved::VISIBILITY_VISIBLE,
            ProductVisibilityResolved::VISIBILITY_HIDDEN
        );

        $qb = $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->createQueryBuilder('pv')
            ->select(
                'pv.id',
                'IDENTITY(pv.website)',
                'IDENTITY(pv.product)',
                $visibilityCondition,
                (string)ProductVisibilityResolved::SOURCE_STATIC
            )
            ->where('pv.visibility != :config')
            ->setParameter('config', ProductVisibility::CONFIG);

        if ($website) {
            $qb->andWhere('pv.website = :website')
                ->setParameter('website', $website);
        }

        $executor->execute(
            $this->getClassName(),
            ['sourceProductVisibility', 'website', 'product', 'visibility', 'source'],
            $qb
        );
    }

    /**
     * @param Website $website
     * @return int
     */
    public function clearTable(Website $website = null)
    {
        // TRUNCATE can't be used because it can't be rolled back in case of DB error
        $qb = $this->createQueryBuilder('pvr')
            ->delete();

        if ($website) {
            $qb->andWhere('pvr.website = :website')
                ->setParameter('website', $website);
        }

        return $qb->getQuery()
            ->execute();
    }

    /**
     * @param Product $product
     * @param Website $website
     * @return null|ProductVisibilityResolved
     */
    public function findByPrimaryKey(Product $product, Website $website)
    {
        return $this->findOneBy(['website' => $website, 'product' => $product]);
    }
}
