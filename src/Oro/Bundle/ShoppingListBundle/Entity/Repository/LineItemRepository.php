<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\ProductBundle\Entity\Product;
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
     * @param array|Product $products
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
     * @param Product[] $products
     * @return array|LineItem[]
     */
    public function getItemsByShoppingListAndProducts(ShoppingList $shoppingList, $products)
    {
        $qb = $this->createQueryBuilder('li');
        $qb->select('li')
            ->where('li.shoppingList = :shoppingList', $qb->expr()->in('li.product', ':product'))
            ->setParameter('shoppingList', $shoppingList)
            ->setParameter('product', $products);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Product $product
     * @param AccountUser $accountUser
     * @return array|LineItem[]
     */
    public function getOneProductLineItemsWithShoppingListNames(Product $product, AccountUser $accountUser)
    {
        $qb = $this->createQueryBuilder('li')
            ->select('li, shoppingList')
            ->join('li.shoppingList', 'shoppingList')
            ->andWhere('li.product = :product')
            ->andWhere('li.accountUser = :accountUser')
            ->setParameter('product', $product)
            ->setParameter('accountUser', $accountUser);

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
     * @param ShoppingList[] $shoppingLists
     * @param int $productCount
     *
     * @return array
     */
    public function getLastProductsGroupedByShoppingList($shoppingLists, $productCount)
    {
        $sql = <<<SQL
SELECT
  li.id AS lineItemId,
  li.shopping_list_id AS shoppingListId,
  product.id AS productId,
  product_name_loc.id AS productNameLocId,
  product_name_loc.fallback AS productNameLocFallback,
  product_name_loc.string AS productNameLocString,
  product_name_loc.text AS productNameLocText,
  product_name_loc.localization_id AS productNameLocLocalizationId
FROM orob2b_shopping_list_line_item li
INNER JOIN orob2b_product product ON product.id = li.product_id
INNER JOIN orob2b_product_name product_name ON product.id = product_name.product_id
INNER JOIN oro_fallback_localization_val product_name_loc ON product_name_loc.id = product_name.localized_value_id
WHERE li.shopping_list_id IN (?) AND (
	SELECT COUNT(*) FROM orob2b_shopping_list_line_item AS li2
	WHERE li2.shopping_list_id = li.shopping_list_id AND li2.id >= li.id
	ORDER BY li2.id DESC
) <= ?
ORDER BY li.shopping_list_id DESC, li.id DESC
SQL;

        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm
            ->addEntityResult('Oro\Bundle\ShoppingListBundle\Entity\LineItem', 'li')
            ->addFieldResult('li', 'lineItemId', 'id')
            ->addJoinedEntityResult('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', 'list', 'li', 'shoppingList')
            ->addFieldResult('list', 'shoppingListId', 'id')
            ->addJoinedEntityResult('Oro\Bundle\ProductBundle\Entity\Product', 'product', 'li', 'product')
            ->addFieldResult('product', 'productId', 'id')
            ->addJoinedEntityResult(
                'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                'names',
                'product',
                'names'
            )
            ->addFieldResult('names', 'productNameLocId', 'id')
            ->addFieldResult('names', 'productNameLocFallback', 'fallback')
            ->addFieldResult('names', 'productNameLocString', 'string')
            ->addFieldResult('names', 'productNameLocText', 'text')
            ->addJoinedEntityResult(
                'Oro\Bundle\LocaleBundle\Entity\Localization',
                'localization',
                'names',
                'localization'
            )
            ->addFieldResult('localization', 'productNameLocLocalizationId', 'id')
        ;

        $shoppingListIds = array_map(
            function ($shoppingList) {
                return $shoppingList->getId();
            },
            $shoppingLists
        );

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter(1, $shoppingListIds);
        $query->setParameter(2, $productCount);

        $lineItems = $query->getResult();

        $result = [];
        foreach ($lineItems as $lineItem) {
            $shoppingListId = $lineItem->getShoppingList()->getId();
            $productName = $lineItem->getProduct()->getName()->getString();

            $result[$shoppingListId][] = [
                'name' => $productName
            ];
        }

        return $result;
    }
}
