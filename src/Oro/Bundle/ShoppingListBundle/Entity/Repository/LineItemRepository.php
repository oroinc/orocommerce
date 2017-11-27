<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LineItemRepository extends EntityRepository
{
    /**
     * Find line item with the same product and unit
     *
     * @param LineItem $lineItem
     *
     * @return LineItem
     */
    public function findDuplicate(LineItem $lineItem)
    {
        $qb = $this->createQueryBuilder('li');
        $qb->where('li.product = :product')
            ->andWhere('li.unit = :unit')
            ->andWhere('li.shoppingList = :shoppingList')
            ->setParameter('product', $lineItem->getProduct())
            ->setParameter('unit', $lineItem->getUnit())
            ->setParameter('shoppingList', $lineItem->getShoppingList())
            ->addOrderBy($qb->expr()->asc('li.id'));

        if ($lineItem->getId()) {
            $qb
                ->andWhere('li.id != :currentId')
                ->setParameter('currentId', $lineItem->getId());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param array|Product $products
     * @return LineItem[]
     */
    public function getProductItemsWithShoppingListNames(AclHelper $aclHelper, $products)
    {
        $qb = $this->createQueryBuilder('li');
        $qb->select('li, shoppingList')
            ->join('li.shoppingList', 'shoppingList')
            ->join('li.product', 'product')
            ->leftJoin('product.parentVariantLinks', 'parentVariantLinks')
            ->andWhere('product IN (:products)')
            ->orWhere('li.parentProduct IN (:products)')
            ->orWhere('parentVariantLinks.parentProduct IN (:products)')
            ->setParameter('products', $products)
            ->addOrderBy($qb->expr()->asc('li.id'));

        return $aclHelper->apply($qb, 'EDIT')->getResult();
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array|LineItem[]
     */
    public function getItemsWithProductByShoppingList(ShoppingList $shoppingList)
    {
        $qb = $this->createQueryBuilder('li');
        $qb->select('li, product, names')
            ->join('li.product', 'product')
            ->join('product.names', 'names')
            ->where('li.shoppingList = :shoppingList')
            ->setParameter('shoppingList', $shoppingList)
            ->addOrderBy($qb->expr()->asc('li.id'));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product[] $products
     * @return array|LineItem[]
     */
    public function getItemsByShoppingListAndProducts(ShoppingList $shoppingList, $products)
    {
        $qb = $this->createQueryBuilder('li');
        $qb->select('li')
            ->where('li.shoppingList = :shoppingList', $qb->expr()->in('li.product', ':product'))
            ->setParameter('shoppingList', $shoppingList)
            ->setParameter('product', $products)
            ->addOrderBy($qb->expr()->asc('li.id'));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Product $product
     * @param CustomerUser $customerUser
     * @return array|LineItem[]
     */
    public function getOneProductLineItemsWithShoppingListNames(Product $product, CustomerUser $customerUser)
    {
        $qb = $this->createQueryBuilder('li');
        $qb->select('li, shoppingList')
            ->join('li.shoppingList', 'shoppingList')
            ->andWhere('li.product = :product')
            ->andWhere('li.customerUser = :customerUser')
            ->setParameter('product', $product)
            ->setParameter('customerUser', $customerUser)
            ->addOrderBy($qb->expr()->asc('li.id'));

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns array where Shopping List id is a key and array of last added products is a value
     *
     * Example:
     * [
     *   74 => [
     *     ['name' => '220 Lumen Rechargeable Headlamp'],
     *     ['name' => 'Credit Card Pin Pad Reader']
     *   ]
     * ]
     *
     * @param ShoppingList[]    $shoppingLists
     * @param int               $productCount
     * @param Localization|null $localization
     *
     * @return array
     */
    public function getLastProductsGroupedByShoppingList(
        array $shoppingLists,
        $productCount,
        Localization $localization = null
    ) {
        $dql = <<<DQL
SELECT IDENTITY(li.shoppingList) as shoppingListId, IDENTITY(li.product) as productId
FROM OroShoppingListBundle:LineItem AS li
WHERE li.shoppingList IN (:shoppingLists) AND (
    SELECT COUNT(li2.id) FROM OroShoppingListBundle:LineItem AS li2
    WHERE li2.shoppingList = li.shoppingList AND li2.id >= li.id
) <= :productCount
ORDER BY li.shoppingList DESC, li.id DESC
DQL;
        $shoppingListIds = array_map(
            function (ShoppingList $shoppingList) {
                return $shoppingList->getId();
            },
            $shoppingLists
        );

        /** @var LineItem[] $lineItems */
        $lineItems = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('shoppingLists', $shoppingListIds)
            ->setParameter('productCount', $productCount)
            ->getArrayResult();

        $result = [];

        $productsIds = array_map(
            function ($lineItem) {
                return $lineItem['productId'];
            },
            $lineItems
        );
        if (count($productsIds) > 0) {
            $qb = $this->_em->createQueryBuilder();
            $products = $qb->select('product, names')
                ->from(Product::class, 'product')
                ->join('product.names', 'names')
                ->where('product IN (:products)')
                ->setParameter('products', $productsIds)
                ->getQuery()
                ->getResult();
            $organizedProducts = [];
            foreach ($products as $product) {
                $organizedProducts[$product->getId()] = $product;
            }
            foreach ($lineItems as $lineItem) {
                $shoppingListId = $lineItem['shoppingListId'];
                $product = $organizedProducts[$lineItem['productId']];
                if ($product !== null) {
                    $result[$shoppingListId][] = [
                        'name' =>  $product->getName($localization)->getString()
                    ];
                }
            }
        }


        return $result;
    }
}
