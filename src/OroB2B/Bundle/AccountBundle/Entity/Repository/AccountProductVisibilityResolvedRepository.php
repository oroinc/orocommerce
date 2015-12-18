<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\BasicOperationRepositoryTrait;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - account
 *  - website
 *  - product
 */
class AccountProductVisibilityResolvedRepository extends EntityRepository
{
    use BasicOperationRepositoryTrait;

    /**
     * @param Account $account
     * @param Product $product
     * @param Website $website
     * @return null|AccountProductVisibilityResolved
     */
    public function findByPrimaryKey(Account $account, Product $product, Website $website)
    {
        return $this->findOneBy(['account' => $account, 'website' => $website, 'product' => $product]);
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
            AccountProductVisibility::HIDDEN => [
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => AccountProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ],
            AccountProductVisibility::VISIBLE => [
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => AccountProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ],
            AccountProductVisibility::CURRENT_PRODUCT => [
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
                'source' => AccountProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ],
        ];
        if ($category) {
            $categoryVisibility = $isCategoryVisible ? AccountProductVisibilityResolved::VISIBILITY_VISIBLE :
                AccountProductVisibilityResolved::VISIBILITY_HIDDEN;
            $visibilityMap[AccountProductVisibility::CATEGORY] = [
                'visibility' => $categoryVisibility,
                'source' => AccountProductVisibilityResolved::SOURCE_CATEGORY,
                'category' => $category->getId(),
            ];
        }

        foreach ($visibilityMap as $visibility => $productVisibility) {
            $qb = $this->getEntityManager()
                ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
                ->createQueryBuilder('productVisibility');
            $fieldsInsert = ['sourceProductVisibility', 'product', 'website', 'account', 'visibility', 'source'];
            $fieldsSelect = [
                'productVisibility.id',
                'IDENTITY(productVisibility.product)',
                'IDENTITY(productVisibility.website)',
                'IDENTITY(productVisibility.account)',
                (string)$productVisibility['visibility'],
                (string)$productVisibility['source'],
            ];
            if ($productVisibility['category']) {
                $fieldsSelect[] = (string)$productVisibility['category'];
                $fieldsInsert[] = 'category';
            }
            $qb->select($fieldsSelect)
                ->where('productVisibility.product = :product')
                ->andWhere('productVisibility.visibility = :visibility')
                ->setParameter('product', $product)
                ->setParameter('visibility', $visibility);

            $insertFromSelect->execute(
                $this->getEntityName(),
                $fieldsInsert,
                $qb
            );
        }
    }
}
