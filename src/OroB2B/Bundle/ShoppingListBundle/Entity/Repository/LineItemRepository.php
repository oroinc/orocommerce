<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

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
        $qb = $this->createQueryBuilder('li')
            ->where('li.product = :product')
            ->andWhere('li.unit = :unit')
            ->andWhere('li.shoppingList = :shoppingList')
            ->setParameter('product', $lineItem->getProduct())
            ->setParameter('unit', $lineItem->getUnit())
            ->setParameter('shoppingList', $lineItem->getShoppingList());

        if ($lineItem->getId()) {
            $qb
                ->andWhere('li.id != :currentId')
                ->setParameter('currentId', $lineItem->getId());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $products
     * @param AccountUser $accountUser
     * @return array|LineItem[]
     */
    public function getProductItemsWithShoppingListNames($products, $accountUser)
    {
        $qb = $this->createQueryBuilder('li')
            ->select('li, shoppingList')
            ->join('li.shoppingList', 'shoppingList')
            ->andWhere('li.accountUser = :accountUser')
            ->andWhere('li.product IN (:products)')
            ->setParameter('products', $products)
            ->setParameter('accountUser', $accountUser);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array|LineItem[]
     */
    public function getItemsWithProductByShoppingList(ShoppingList $shoppingList)
    {
        $qb = $this->createQueryBuilder('li')
            ->select('li, product, names')
            ->join('li.product', 'product')
            ->join('product.names', 'names')
            ->where('li.shoppingList = :shoppingList')
            ->setParameter('shoppingList', $shoppingList);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @return array|LineItem[]
     */
    public function getItemsByShoppingListAndProduct(ShoppingList $shoppingList, Product $product)
    {
        $qb = $this->createQueryBuilder('li')
            ->select('li')
            ->where('li.shoppingList = :shoppingList', 'li.product = :product')
            ->setParameter('shoppingList', $shoppingList)
            ->setParameter('product', $product);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Product $product
     * @param AccountUser $accountUser
     * @return array|LineItem[]
     */
    public function getOneProductItemsWithShoppingListNames(
        Product $product,
        AccountUser $accountUser
    ) {
        $qb = $this->createQueryBuilder('li')
            ->select('li, shoppingList')
            ->join('li.shoppingList', 'shoppingList')
            ->andWhere('li.product = :product')
            ->andWhere('li.accountUser = :accountUser')
            ->setParameter('product', $product)
            ->setParameter('accountUser', $accountUser);
        return $qb->getQuery()->getResult();
    }
}
