<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResetCustomerUserTrait;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResettableCustomerUserRepositoryInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * A repository for ShoppingList entities.
 */
class ShoppingListRepository extends EntityRepository implements ResettableCustomerUserRepositoryInterface
{
    use ResetCustomerUserTrait;

    /**
     * @param AclHelper $aclHelper
     * @param bool $selectRelations
     *
     * @return ShoppingList|null
     */
    public function findAvailableForCustomerUser(AclHelper $aclHelper, $selectRelations = false)
    {
        /** @var ShoppingList $shoppingList */
        $qb = $this->getShoppingListQueryBuilder($selectRelations);
        $qb->addOrderBy('list.id', 'DESC')->setMaxResults(1);

        return $aclHelper->apply($qb)->getOneOrNullResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param array $sortCriteria
     * @param ShoppingList|int|null $excludeShoppingList
     *
     * @return array
     */
    public function findByUser(
        AclHelper $aclHelper,
        array $sortCriteria = [],
        $excludeShoppingList = null
    ) {
        $qb = $this->createQueryBuilder('list')
            ->select('list, items')
            ->leftJoin('list.lineItems', 'items');

        if ($excludeShoppingList) {
            $qb->andWhere($qb->expr()->neq('list.id', ':excludeShoppingList'))
                ->setParameter('excludeShoppingList', $excludeShoppingList);
        }

        foreach ($sortCriteria as $field => $sortOrder) {
            QueryBuilderUtil::checkField($field);
            if ($sortOrder === Criteria::ASC) {
                $qb->addOrderBy($qb->expr()->asc($field));
            } elseif ($sortOrder === Criteria::DESC) {
                $qb->addOrderBy($qb->expr()->desc($field));
            }
        }

        return $aclHelper->apply($qb, BasicPermission::VIEW, [AclHelper::CHECK_RELATIONS => false])->getResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param int $id
     *
     * @return ShoppingList|null
     */
    public function findByUserAndId(AclHelper $aclHelper, $id)
    {
        $qb = $this->createQueryBuilder('list')
            ->select('list')
            ->andWhere('list.id = :id')
            ->setParameter('id', $id, Type::INTEGER);

        return $aclHelper->apply($qb)->getOneOrNullResult();
    }

    /**
     * @param bool $selectRelations
     *
     * @return QueryBuilder
     */
    protected function getShoppingListQueryBuilder($selectRelations = false)
    {
        $qb = $this->createQueryBuilder('list')
            ->select('list');
        if ($selectRelations) {
            $this->modifyQbWithRelations($qb);
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     */
    protected function modifyQbWithRelations(QueryBuilder $qb)
    {
        $qb->addSelect('items', 'product', 'images', 'imageTypes', 'imageFile', 'unitPrecisions')
            ->leftJoin('list.lineItems', 'items')
            ->leftJoin('items.product', 'product')
            ->leftJoin('product.images', 'images')
            ->leftJoin('images.types', 'imageTypes')
            ->leftJoin('images.image', 'imageFile')
            ->leftJoin('product.unitPrecisions', 'unitPrecisions');
    }

    /**
     * @param int $customerId
     * @param int $organizationId
     * @param $website int|Website
     * @return int
     */
    public function countUserShoppingLists($customerId, $organizationId, $website)
    {
        $results = $this->createQueryBuilder('shopping_list')
            ->select('COUNT(shopping_list)')
            ->where('shopping_list.customerUser=:customerUser')
            ->andWhere('shopping_list.organization=:organization')
            ->andWhere('shopping_list.website=:website')
            ->setParameter('customerUser', $customerId)
            ->setParameter('organization', $organizationId)
            ->setParameter('website', $website)
            ->getQuery()
            ->getSingleScalarResult();

        return (integer) $results;
    }

    /**
     * Used in ShoppingListController::viewAction().
     * Loads related entities to eliminate extra queries when displaying on view page.
     *
     * @param int $shoppingListId
     *
     * @return ShoppingList|null
     */
    public function findForViewAction(int $shoppingListId): ?ShoppingList
    {
        $qb = $this->createQueryBuilder('shopping_list');

        /** @var ShoppingList $shoppingList */
        $shoppingList = $qb
            ->select(
                'shopping_list',
                'line_item',
                'product',
                'category',
                'product_minimum_quantity',
                'product_maximum_quantity',
                'product_highlight_low_inventory',
                'product_is_upcoming',
                'category_minimum_quantity',
                'category_maximum_quantity',
                'category_highlight_low_inventory',
                'category_is_upcoming'
            )
            ->leftJoin('shopping_list.lineItems', 'line_item')
            ->leftJoin('line_item.product', 'product')
            ->leftJoin('product.category', 'category')
            ->leftJoin('product.highlightLowInventory', 'product_highlight_low_inventory')
            ->leftJoin('product.isUpcoming', 'product_is_upcoming')
            ->leftJoin('product.minimumQuantityToOrder', 'product_minimum_quantity')
            ->leftJoin('product.maximumQuantityToOrder', 'product_maximum_quantity')
            ->leftJoin('category.highlightLowInventory', 'category_highlight_low_inventory')
            ->leftJoin('category.isUpcoming', 'category_is_upcoming')
            ->leftJoin('category.minimumQuantityToOrder', 'category_minimum_quantity')
            ->leftJoin('category.maximumQuantityToOrder', 'category_maximum_quantity')
            ->where($qb->expr()->eq('shopping_list.id', ':shoppingListId'))
            ->setParameter('shoppingListId', $shoppingListId, Type::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        if ($shoppingList && $shoppingList->getLineItems()->count()) {
            $lineItems = $shoppingList->getLineItems();
            $productsIds = [];
            foreach ($lineItems as $lineItem) {
                $productId = $lineItem->getProduct()->getId();
                $productsIds[$productId] = $productId;
            }

            $this->loadRelatedProductNames($productsIds);
            $this->loadRelatedProductUnitPrecisions($productsIds);
            $this->loadRelatedProductImages($productsIds);
        }

        return $shoppingList;
    }

    /**
     * @param array $productIds
     */
    private function loadRelatedProductNames(array $productIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('partial product.{id}', 'product_name')
            ->from(Product::class, 'product')
            ->innerJoin('product.names', 'product_name')
            ->where($qb->expr()->in('product', ':products'))
            ->setParameter('products', $productIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $productIds
     */
    private function loadRelatedProductUnitPrecisions(array $productIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('partial product.{id}', 'unit_precision', 'primary_unit_precision')
            ->from(Product::class, 'product')
            ->leftJoin('product.unitPrecisions', 'unit_precision')
            ->leftJoin('product.primaryUnitPrecision', 'primary_unit_precision')
            ->where($qb->expr()->in('product', ':products'))
            ->setParameter('products', $productIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $productsIds
     */
    private function loadRelatedProductImages(array $productsIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('partial product.{id}', 'product_image', 'product_image_image', 'product_image_type')
            ->from(Product::class, 'product')
            ->leftJoin('product.images', 'product_image')
            ->leftJoin('product_image.image', 'product_image_image')
            ->leftJoin('product_image.types', 'product_image_type')
            ->where($qb->expr()->in('product', ':products'))
            ->setParameter('products', $productsIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @param int[]|ShoppingList[] $shoppingLists
     * @return array
     */
    public function getLineItemsCount(array $shoppingLists): array
    {
        $qb = $this->createQueryBuilder('sl');
        $qb->resetDQLPart('select')
            ->select('sl.id AS id')
            ->addSelect('COUNT(li.id) AS count')
            ->addSelect('IDENTITY(li.parentProduct) AS withParent')
            ->leftJoin('sl.lineItems', 'li')
            ->where($qb->expr()->in('sl.id', ':shopping_lists'))
            ->setParameter('shopping_lists', $shoppingLists)
            ->groupBy('sl.id, li.parentProduct');

        $result = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            if (!isset($result[$row['id']])) {
                $result[$row['id']] = 0;
            }

            $result[$row['id']] += $row['withParent'] ? 1 : $row['count'];
        }

        return $result;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param int $count
     */
    public function setLineItemsCount(ShoppingList $shoppingList, int $count): void
    {
        $entityManager = $this->getEntityManager();

        $qb = $entityManager->createQueryBuilder();
        $qb->update($this->getClassName(), 'sl')
            ->set('sl.line_items_count', ':count')
            ->andWhere($qb->expr()->eq('sl.id', ':id'))
            ->setParameter('count', $count)
            ->setParameter('id', $shoppingList->getId())
            ->getQuery()
            ->execute();

        $entityManager->refresh($shoppingList);
    }
}
