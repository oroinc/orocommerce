<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Entity repository for Shopping List Line Item entity
 */
class LineItemRepository extends ServiceEntityRepository
{
    /**
     * Find line item with the same product and unit in the specified shopping list
     */
    public function findDuplicateInShoppingList(LineItem $lineItem, ?ShoppingList $shoppingList): ?LineItem
    {
        $shoppingListId = $shoppingList?->getId();
        if ($shoppingListId === null) {
            return null;
        }

        $qb = $this->createQueryBuilder('li');
        $qb
            ->where('li.product = :product')
            ->andWhere('li.unit = :unit')
            ->andWhere('li.checksum = :checksum')
            ->andWhere('li.shoppingList = :shoppingList')
            ->setParameter('product', $lineItem->getProduct())
            ->setParameter('unit', $lineItem->getUnit())
            ->setParameter('checksum', $lineItem->getChecksum())
            ->setParameter('shoppingList', $shoppingListId)
            ->addOrderBy($qb->expr()->asc('li.id'))
            ->setMaxResults(1);

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
     * @param CustomerUser|null $customerUser
     * @return LineItem[]
     */
    public function getProductItemsWithShoppingListNames(
        AclHelper $aclHelper,
        $products,
        ?CustomerUser $customerUser = null
    ): array {
        $qb = $this->createQueryBuilder('li');
        $qb->select('li, shoppingList')
            ->join('li.shoppingList', 'shoppingList')
            ->join('li.product', 'product')
            ->leftJoin(
                'li.product',
                'productExpr',
                Join::WITH,
                'li.product = productExpr AND productExpr IN (:products)'
            )
            ->leftJoin(
                'product.parentVariantLinks',
                'parentVariantLinksExpr',
                Join::WITH,
                'product = parentVariantLinksExpr.parentProduct'.
                ' AND parentVariantLinksExpr.parentProduct IN (:products)'
            );

        if ($customerUser) {
            $qb->where($qb->expr()->eq('shoppingList.customerUser', ':customerUser'))
                ->setParameter('customerUser', $customerUser);
        }

        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->isNull('li.parentProduct'),
            $qb->expr()->in('li.parentProduct', ':products'),
            $qb->expr()->isNotNull('productExpr'),
            $qb->expr()->isNotNull('parentVariantLinksExpr')
        ))
        ->setParameter('products', $products)
        ->addOrderBy($qb->expr()->asc('li.id'));

        return $aclHelper->apply($qb, BasicPermission::EDIT)->getResult();
    }

    public function hasEmptyMatrix(int $shoppingListId): bool
    {
        $qb = $this->createQueryBuilder('li');
        $qb->select('li.quantity, p.type, p.id, IDENTITY(li.parentProduct) as parent')
            ->join('li.product', 'p')
            ->where($qb->expr()->eq('li.shoppingList', ':shoppingListId'))
            ->setParameter('shoppingListId', $shoppingListId);

        $configurable = [];
        $simple = [];

        foreach ($qb->getQuery()->getArrayResult() as $row) {
            if ($row['type'] === Product::TYPE_CONFIGURABLE) {
                $configurable[] = $row['id'];
                continue;
            }

            if (!$row['parent'] || $row['quantity'] <= 0) {
                continue;
            }

            $simple[$row['parent']][] = $row['id'];
        }

        foreach ($configurable as $id) {
            if (!isset($simple[$id]) || !count($simple[$id])) {
                return true;
            }
        }

        return false;
    }

    public function canBeGrouped(int $shoppingListId): bool
    {
        $qb = $this->createQueryBuilder('li');
        $qb->resetDQLPart('select')
            ->select($qb->expr()->count('li.id'))
            ->where(
                $qb->expr()->in('li.shoppingList', ':shopping_list'),
                $qb->expr()->isNotNull('li.parentProduct')
            )
            ->setParameter('shopping_list', $shoppingListId)
            ->groupBy('li.parentProduct')
            ->having($qb->expr()->gt($qb->expr()->count('li.id'), 1))
            ->setMaxResults(1);

        return (bool) $qb->getQuery()->getOneOrNullResult();
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
        if (!$shoppingLists) {
            return [];
        }

        $qb = $this->createQueryBuilder('line_item');
        $query = $qb
            ->select('COALESCE(parent_product.id, product.id) as main_product_id')
            ->innerJoin('line_item.product', 'product')
            ->leftJoin('line_item.parentProduct', 'parent_product')
            ->andWhere($qb->expr()->eq('line_item.shoppingList', ':shopping_list'))
            ->addGroupBy('main_product_id')
            ->addOrderBy($qb->expr()->desc($qb->expr()->max('line_item.id')))
            ->setMaxResults($productCount)
            ->getQuery();

        $productsIdsByShoppingList = [];
        foreach ($shoppingLists as $shoppingList) {
            $shoppingListId = $shoppingList->getId();

            $productsIdsByShoppingList[$shoppingListId] = \array_column(
                $query->execute(['shopping_list' => $shoppingListId], AbstractQuery::HYDRATE_ARRAY),
                'main_product_id'
            );
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('product', 'names')
            ->from(Product::class, 'product', 'product.id')
            ->innerJoin('product.names', 'names')
            ->where($qb->expr()->in('product', ':product_ids'))
            ->setParameter('product_ids', \array_merge(...\array_values($productsIdsByShoppingList)));

        /** @var Product[] $products */
        $products = $qb->getQuery()->getResult();

        $result = [];
        foreach ($productsIdsByShoppingList as $shoppingListId => $productsIds) {
            foreach ($productsIds as $productId) {
                if (!isset($products[$productId])) {
                    continue;
                }

                $result[$shoppingListId][] = [
                    'name' => $products[$productId]->getName($localization)->getString(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param array $allowedInventoryStatuses
     * @return int Number of deleted line items
     */
    public function deleteNotAllowedLineItemsFromShoppingList(
        ShoppingList $shoppingList,
        array $allowedInventoryStatuses
    ): int {
        $lineItemsQb = $this->createQueryBuilder('line_item');
        $lineItemsQuery = $lineItemsQb
            ->select('line_item.id')
            ->innerJoin('line_item.product', 'product')
            ->where(
                $lineItemsQb->expr()->orX(
                    $lineItemsQb->expr()->notIn('IDENTITY(product.inventory_status)', ':allowedInventoryStatuses'),
                    $lineItemsQb->expr()->eq('product.status', ':status')
                ),
                $lineItemsQb->expr()->eq('line_item.shoppingList', ':shoppingList')
            )
            ->setParameter('allowedInventoryStatuses', $allowedInventoryStatuses)
            ->setParameter('status', Product::STATUS_DISABLED)
            ->setParameter('shoppingList', $shoppingList)
            ->getQuery();

        $identifierHydrationMode = 'IdentifierHydrator';

        $lineItemsQuery->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode($identifierHydrationMode, IdentifierHydrator::class);

        $ids = $lineItemsQuery->getResult($identifierHydrationMode);
        $deletedCount = 0;
        if ($ids) {
            $deleteQb = $this->getEntityManager()->createQueryBuilder();
            $deletedCount = $deleteQb->delete()
                ->from($this->getEntityName(), 'line_item')
                ->where($deleteQb->expr()->in('line_item.id', ':ids'))
                ->getQuery()
                ->execute(['ids' => $ids]);
        }

        return $deletedCount;
    }

    public function findLineItemsByParentProductAndUnit(int $shoppingListId, int $productId, string $unitCode): array
    {
        $expr = $this->getEntityManager()->getExpressionBuilder();

        return $this
            ->createQueryBuilder('line_item')
            ->where($expr->eq('IDENTITY(line_item.shoppingList)', ':shopping_list_id'))
            ->andWhere(
                $expr->orX(
                    $expr->eq('line_item.parentProduct', ':product_id'),
                    $expr->eq('line_item.product', ':product_id')
                )
            )
            ->andWhere($expr->eq('line_item.unit', ':unit_code'))
            ->getQuery()
            ->execute(['shopping_list_id' => $shoppingListId, 'product_id' => $productId, 'unit_code' => $unitCode]);
    }
}
