<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - accountGroup
 *  - website
 *  - product
 */
class AccountGroupProductVisibilityResolvedRepository extends EntityRepository
{
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
}
