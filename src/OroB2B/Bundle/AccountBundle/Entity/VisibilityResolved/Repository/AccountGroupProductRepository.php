<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
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
    use BasicOperationRepositoryTrait;

    /**
     * {@inheritdoc}
     */
    public function insertByCategory(
        InsertFromSelectQueryExecutor $insertFromSelect,
        $cacheVisibility,
        $categories,
        $accountGroupId,
        $websiteId = null
    ) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select(
                'agpv.id',
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

        if ($websiteId) {
            $queryBuilder->andWhere('IDENTITY(agpv.website) = :website')
                ->setParameter('website', $websiteId);
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
            $queryBuilder
        );
    }

    /**
     * {@inheritdoc}
     */
    public function insertStatic(InsertFromSelectQueryExecutor $insertFromSelect, $websiteId = null)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
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

        if ($websiteId) {
            $queryBuilder->andWhere('IDENTITY(agpv.website) = :website')
                ->setParameter('website', $websiteId);
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
     * @param boolean $isCategoryVisible
     * @param Category|null $category
     */
    public function insertByProduct(
        Product $product,
        InsertFromSelectQueryExecutor $insertFromSelect,
        $isCategoryVisible,
        Category $category = null
    ) {
        $visibilityMap = [
            AccountGroupProductVisibility::HIDDEN => [
                'visibility' => AccountGroupProductVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => AccountGroupProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ],
            AccountGroupProductVisibility::VISIBLE => [
                'visibility' => AccountGroupProductVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => AccountGroupProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ],
        ];
        if ($category) {
            $categoryVisibility = $isCategoryVisible ? AccountGroupProductVisibilityResolved::VISIBILITY_VISIBLE :
                AccountGroupProductVisibilityResolved::VISIBILITY_HIDDEN;
            $visibilityMap[AccountGroupProductVisibility::CATEGORY] = [
                'visibility' => $categoryVisibility,
                'source' => AccountGroupProductVisibilityResolved::SOURCE_CATEGORY,
                'category' => $category->getId()
            ];
        }

        foreach ($visibilityMap as $visibility => $productVisibility) {
            $qb = $this->getEntityManager()
                ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
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

            $fields = ['sourceProductVisibility', 'product', 'website', 'accountGroup', 'visibility', 'source'];
            if ($productVisibility['category']) {
                $qb->addSelect((string)$productVisibility['category']);
                $fields[] = 'category';
            }

            $insertFromSelect->execute(
                $this->getEntityName(),
                $fields,
                $qb
            );
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
