<?php

namespace Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\CustomerBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - website
 *  - product
 */
class ProductRepository extends AbstractVisibilityRepository
{
    use BasicOperationRepositoryTrait;

    /**
     * @param InsertFromSelectQueryExecutor $executor
     * @param Website|null $website
     */
    public function insertByCategory(InsertFromSelectQueryExecutor $executor, Website $website = null)
    {
        $qb = $this->getEntityManager()
            ->getRepository('OroCatalogBundle:Category')
            ->createQueryBuilder('category');

        $websiteJoinCondition = '1 = 1';
        if ($website) {
            $websiteJoinCondition = 'website = :website';
            $qb->setParameter('website', $website);
        }

        $configValue = ProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb->select([
            'website.id',
            'product.id',
            'COALESCE(cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
            (string)ProductVisibilityResolved::SOURCE_CATEGORY,
            'category.id',
        ])
        ->innerJoin('category.products', 'product')
        ->innerJoin('OroWebsiteBundle:Website', 'website', Join::WITH, $websiteJoinCondition)
        ->leftJoin(
            'OroCustomerBundle:Visibility\ProductVisibility',
            'pv',
            'WITH',
            'IDENTITY(pv.product) = product.id AND IDENTITY(pv.website) = website.id'
        )
        ->leftJoin(
            'OroCustomerBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            'WITH',
            'cvr.category = category'
        )
        ->where('pv.id is null');

        if ($website) {
            $qb->andWhere('pv.website = :website')
                ->setParameter('website', $website);
        }

        $executor->execute(
            $this->getClassName(),
            ['website', 'product', 'visibility', 'source', 'category'],
            $qb
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $executor
     * @param Website|null $website
     * @param Product|null $product
     */
    public function insertStatic(
        InsertFromSelectQueryExecutor $executor,
        Website $website = null,
        $product = null
    ) {
        $visibilityCondition = sprintf(
            "CASE WHEN pv.visibility = '%s' THEN %s ELSE %s END",
            ProductVisibility::VISIBLE,
            ProductVisibilityResolved::VISIBILITY_VISIBLE,
            ProductVisibilityResolved::VISIBILITY_HIDDEN
        );

        $qb = $this->getEntityManager()
            ->getRepository('OroCustomerBundle:Visibility\ProductVisibility')
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
        if ($product) {
            $qb->andWhere('pv.product = :product')
                ->setParameter('product', $product);
        }

        $executor->execute(
            $this->getClassName(),
            ['sourceProductVisibility', 'website', 'product', 'visibility', 'source'],
            $qb
        );
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

    /**
     * @param Product $product
     */
    public function deleteByProduct(Product $product)
    {
        $this->createQueryBuilder('productVisibility')
            ->delete()
            ->where('productVisibility.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
    }

    /**
     * @param InsertFromSelectQueryExecutor $executor
     * @param Product $product
     * @param int $visibility
     * @param Category $category
     */
    public function insertByProduct(
        InsertFromSelectQueryExecutor $executor,
        Product $product,
        $visibility,
        Category $category = null
    ) {
        $this->insertStatic($executor, null, $product);

        if ($category) {
            $qb = $this->getVisibilitiesByCategoryQb($visibility, [$category->getId()]);

            $qb->andWhere('product = :product')
                ->setParameter('product', $product);

            $executor->execute(
                $this->getClassName(),
                ['website', 'product', 'visibility', 'source', 'category'],
                $qb
            );
        }
    }

    /**
     * @param int $visibility
     * @param array $categoryIds
     * @param Website|null $website
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getVisibilitiesByCategoryQb($visibility, array $categoryIds, Website $website = null)
    {
        $qb = $this->getEntityManager()
            ->getRepository('OroCatalogBundle:Category')
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
            ->innerJoin('OroWebsiteBundle:Website', 'website', Join::WITH, $websiteJoinCondition)
            ->leftJoin(
                'OroCustomerBundle:Visibility\ProductVisibility',
                'pv',
                Join::WITH,
                'IDENTITY(pv.product) = product.id AND IDENTITY(pv.website) = website.id'
            )
            ->where('pv.id is null')
            ->andWhere('category.id in (:ids)')
            ->setParameter('ids', $categoryIds);

        return $qb;
    }
}
