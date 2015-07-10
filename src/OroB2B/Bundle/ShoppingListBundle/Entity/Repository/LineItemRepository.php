<?php
namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LineItemRepository extends EntityRepository
{
    /**
     * Find line item by product and unit
     *
     * @param Product $product
     * @param ProductUnit $unit
     * @param ShoppingList $shoppingList
     *
     * @return LineItem
     */
    public function findByProductAndUnit(ShoppingList $shoppingList, Product $product, ProductUnit $unit)
    {
        $result = $this->createQueryBuilder('li')
            ->where('li.product = :product')
            ->andWhere('li.unit = :unit')
            ->andWhere('li.shoppingList = :shoppingList')
            ->setParameter('product', $product)
            ->setParameter('unit', $unit)
            ->setParameter('shoppingList', $shoppingList)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }
}
