<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - accountGroup
 *  - website
 *  - product
 */
class AccountGroupProductRepository extends AbstractVisibilityRepository
{
    use BasicOperationRepositoryTrait;

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Website|null $website
     */
    public function insertByCategory(InsertFromSelectQueryExecutor $insertFromSelect, Website $website = null)
    {
        $configValue = AccountGroupProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
            ->createQueryBuilder('agpv');
        $qb->select(
            'agpv.id',
            'IDENTITY(agpv.website)',
            'IDENTITY(agpv.product)',
            'IDENTITY(agpv.accountGroup)',
            'COALESCE(agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
            (string)AccountGroupProductVisibilityResolved::SOURCE_CATEGORY,
            'category.id'
        )
        ->innerJoin('OroCatalogBundle:Category', 'category', 'WITH', 'agpv.product MEMBER OF category.products')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
            'agcvr',
            'WITH',
            'agcvr.accountGroup = agpv.accountGroup AND agcvr.category = category'
        )
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            'WITH',
            'cvr.category = category'
        )
        ->andWhere('agpv.visibility = :categoryVisibility')
        ->setParameter('categoryVisibility', AccountGroupProductVisibility::CATEGORY);

        if ($website) {
            $qb->andWhere('agpv.website = :website')
                ->setParameter('website', $website);
        }

        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'sourceProductVisibility',
                'website',
                'product',
                'accountGroup',
                'visibility',
                'source',
                'category',
            ],
            $qb
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Website|null $website
     */
    public function insertStatic(InsertFromSelectQueryExecutor $insertFromSelect, Website $website = null)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
            ->createQueryBuilder('agpv')
            ->select(
                [
                    'agpv.id',
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
                'sourceProductVisibility',
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
     * @param Product $product
     */
    public function deleteByProduct(Product $product)
    {
        $this->createQueryBuilder('resolvedVisibility')
            ->delete()
            ->where('resolvedVisibility.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Product $product
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Category|null $category
     */
    public function insertByProduct(
        Product $product,
        InsertFromSelectQueryExecutor $insertFromSelect,
        Category $category = null
    ) {
        $visibilityMap = [
            AccountGroupProductVisibility::HIDDEN => [
                'visibility' => AccountGroupProductVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => AccountGroupProductVisibilityResolved::SOURCE_STATIC,
            ],
            AccountGroupProductVisibility::VISIBLE => [
                'visibility' => AccountGroupProductVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => AccountGroupProductVisibilityResolved::SOURCE_STATIC,
            ],
        ];

        $fields = ['sourceProductVisibility', 'product', 'website', 'accountGroup', 'visibility', 'source'];

        foreach ($visibilityMap as $visibility => $productVisibility) {
            $qb = $this->getEntityManager()
                ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
                ->createQueryBuilder('productVisibility');
            $qb->select([
                'productVisibility.id',
                'IDENTITY(productVisibility.product)',
                'IDENTITY(productVisibility.website)',
                'IDENTITY(productVisibility.accountGroup)',
                (string)$productVisibility['visibility'],
                (string)$productVisibility['source'],
            ])
            ->where('productVisibility.product = :product')
            ->andWhere('productVisibility.visibility = :visibility')
            ->setParameter('product', $product)
            ->setParameter('visibility', $visibility);

            $insertFromSelect->execute($this->getEntityName(), $fields, $qb);
        }

        if ($category) {
            $configValue = AccountGroupProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
            $qb = $this->getEntityManager()
                ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
                ->createQueryBuilder('productVisibility');
            $qb->select([
                'productVisibility.id',
                'IDENTITY(productVisibility.product)',
                'IDENTITY(productVisibility.website)',
                'IDENTITY(productVisibility.accountGroup)',
                'COALESCE(agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
                (string)AccountGroupProductVisibilityResolved::SOURCE_CATEGORY,
                (string)$category->getId()
            ])
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
                'agcvr',
                'WITH',
                'agcvr.accountGroup = productVisibility.accountGroup AND agcvr.category = :category'
            )
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                'WITH',
                'cvr.category = :category'
            )
            ->andWhere('productVisibility.product = :product')
            ->andWhere('productVisibility.visibility = :visibility')
            ->setParameter('category', $category)
            ->setParameter('product', $product)
            ->setParameter('visibility', AccountGroupProductVisibility::CATEGORY);

            $fields[] = 'category';
            $insertFromSelect->execute($this->getEntityName(), $fields, $qb);
        }
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
