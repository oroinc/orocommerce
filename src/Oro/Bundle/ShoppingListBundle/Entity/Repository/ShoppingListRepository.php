<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResetCustomerUserTrait;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResettableCustomerUserRepositoryInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
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
        $qb = $this->getFindByUserQueryBuilder($sortCriteria, $excludeShoppingList);

        return $aclHelper->apply($qb, BasicPermission::VIEW, [AclHelper::CHECK_RELATIONS => false])->getResult();
    }

    /**
     * @param array $sortCriteria
     * @param ShoppingList|int|null $excludeShoppingList
     * @return QueryBuilder
     */
    private function getFindByUserQueryBuilder(
        array $sortCriteria = [],
        $excludeShoppingList = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('list')
            ->select('list, items')
            ->leftJoin('list.lineItems', 'items');

        if ($excludeShoppingList) {
            $qb
                ->andWhere($qb->expr()->neq('list.id', ':excludeShoppingList'))
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

        return $qb;
    }

    /**
     * @param int $customerUserId
     * @param AclHelper $aclHelper
     * @param array $sortCriteria
     * @param ShoppingList|int|null $excludeShoppingList
     *
     * @return array
     */
    public function findByCustomerUserId(
        int $customerUserId,
        AclHelper $aclHelper,
        array $sortCriteria = [],
        $excludeShoppingList = null
    ): array {
        $qb = $this->getFindByUserQueryBuilder($sortCriteria, $excludeShoppingList);
        $qb
            ->andWhere($qb->expr()->eq('list.customerUser', ':customerUserId'))
            ->setParameter('customerUserId', $customerUserId);

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
            ->setParameter('id', $id, Types::INTEGER);

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
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->find($shoppingListId);

        if ($shoppingList && $shoppingList->getLineItems()->count()) {
            $this->preloadLineItemsForViewAction($shoppingList->getLineItems()->toArray());
        }

        return $shoppingList;
    }

    /**
     * Loads related entities to eliminate extra queries when displaying on view page in line items grid.
     *
     * @param LineItem[] $lineItems
     */
    public function preloadLineItemsForViewAction(array $lineItems): void
    {
        $productsIds = [];
        $mainProductsIds = [];
        foreach ($lineItems as $lineItem) {
            $productId = $lineItem->getProduct()->getId();
            $productsIds[$productId] = $productId;

            $parentProduct = $lineItem->getParentProduct();
            if ($parentProduct) {
                $productId = $parentProduct->getId();
                $productsIds[$productId] = $productId;
                $mainProductsIds[$productId] = $productId;
            } else {
                $mainProductsIds[$productId] = $productId;
            }
        }

        $products = $this->loadRelatedProducts($productsIds);
        $categoriesIds = [];
        foreach ($products as $product) {
            $category = $product->getCategory();
            if ($category) {
                $categoriesIds[$category->getId()] = $category->getId();
            }
        }

        $this->loadRelatedCategories($categoriesIds);
        $this->loadRelatedEntityFallbackValuesForProducts($productsIds);
        $this->loadRelatedEntityFallbackValuesForCategories($categoriesIds);
        $this->loadRelatedProductNames($mainProductsIds);
        $this->loadRelatedProductImages($productsIds);
        $this->loadRelatedProductUnits($productsIds);
    }

    /**
     * @param array $productsIds
     *
     * @return array
     */
    private function loadRelatedProducts(array $productsIds): array
    {
        return $this->getEntityManager()->getRepository(Product::class)->findBy(['id' => $productsIds]);
    }

    /**
     * @param array $productsIds
     */
    private function loadRelatedEntityFallbackValuesForProducts(array $productsIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select(
                'partial product.{id}',
                'partial product_minimum_quantity.{id,fallback,scalarValue}',
                'partial product_maximum_quantity.{id,fallback,scalarValue}',
                'partial product_highlight_low_inventory.{id,fallback,scalarValue}',
                'partial product_is_upcoming.{id,fallback,scalarValue}'
            )
            ->from(Product::class, 'product')
            ->leftJoin('product.highlightLowInventory', 'product_highlight_low_inventory')
            ->leftJoin('product.isUpcoming', 'product_is_upcoming')
            ->leftJoin('product.minimumQuantityToOrder', 'product_minimum_quantity')
            ->leftJoin('product.maximumQuantityToOrder', 'product_maximum_quantity')
            ->where($qb->expr()->in('product', ':products'))
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->execute(['products' => $productsIds]);
    }

    /**
     * @param array $categoriesIds
     */
    private function loadRelatedCategories(array $categoriesIds): void
    {
        $this->getEntityManager()->getRepository(Category::class)->findBy(['id' => $categoriesIds]);
    }

    /**
     * @param array $categoriesIds
     *
     * @return array
     */
    private function loadRelatedEntityFallbackValuesForCategories(array $categoriesIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select(
                'partial category.{id}',
                'partial category_minimum_quantity.{id,fallback,scalarValue}',
                'partial category_maximum_quantity.{id,fallback,scalarValue}',
                'partial category_highlight_low_inventory.{id,fallback,scalarValue}',
                'partial category_is_upcoming.{id,fallback,scalarValue}'
            )
            ->from(Category::class, 'category')
            ->leftJoin('category.highlightLowInventory', 'category_highlight_low_inventory')
            ->leftJoin('category.isUpcoming', 'category_is_upcoming')
            ->leftJoin('category.minimumQuantityToOrder', 'category_minimum_quantity')
            ->leftJoin('category.maximumQuantityToOrder', 'category_maximum_quantity')
            ->where($qb->expr()->in('category', ':categories_ids'))
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->execute(['categories_ids' => $categoriesIds]);
    }

    /**
     * @param array $productsIds
     */
    private function loadRelatedProductNames(array $productsIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('partial product.{id}', 'partial product_name.{id,fallback,string}', 'localization')
            ->from(Product::class, 'product')
            ->innerJoin('product.names', 'product_name')
            ->leftJoin('product_name.localization', 'localization')
            ->where($qb->expr()->in('product', ':products'))
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->execute(['products' => $productsIds]);
    }

    /**
     * @param array $productsIds
     */
    private function loadRelatedProductImages(array $productsIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select(
                'partial product.{id}',
                'partial product_image.{id}',
                'product_image_image',
                'partial product_image_type.{id,type}'
            )
            ->from(Product::class, 'product')
            ->leftJoin('product.images', 'product_image')
            ->leftJoin('product_image.image', 'product_image_image')
            ->leftJoin('product_image.types', 'product_image_type')
            ->where($qb->expr()->in('product', ':products'))
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->execute(['products' => $productsIds]);
    }

    /**
     * @param array $productsIds
     */
    private function loadRelatedProductUnits(array $productsIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select(
                'partial product.{id}',
                'partial primary_unit_precision.{id,unit,precision,sell}',
                'partial unit_precisions.{id,unit,precision,sell}',
                'partial primary_unit.{code}',
                'partial unit.{code}'
            )
            ->from(Product::class, 'product')
            ->leftJoin('product.primaryUnitPrecision', 'primary_unit_precision')
            ->leftJoin('product.unitPrecisions', 'unit_precisions')
            ->leftJoin('primary_unit_precision.unit', 'primary_unit')
            ->leftJoin('unit_precisions.unit', 'unit')
            ->where($qb->expr()->in('product', ':products'))
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->execute(['products' => $productsIds]);
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
            ->leftJoin('sl.lineItems', 'li')
            ->where($qb->expr()->in('sl.id', ':shopping_lists'))
            ->setParameter('shopping_lists', $shoppingLists)
            ->groupBy('sl.id')
            ->indexBy('sl', 'sl.id');

        return array_map(
            static function (array $item) {
                return $item['count'];
            },
            $qb->getQuery()->getArrayResult()
        );
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
            ->set('sl.lineItemsCount', ':count')
            ->andWhere($qb->expr()->eq('sl.id', ':id'))
            ->setParameter('count', $count)
            ->setParameter('id', $shoppingList->getId())
            ->getQuery()
            ->execute();

        $entityManager->refresh($shoppingList);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return bool
     */
    public function hasEmptyConfigurableLineItems(ShoppingList $shoppingList): bool
    {
        $qb = $this->createQueryBuilder('shopping_list');

        return (bool) $qb
            ->select('1')
            ->innerJoin('shopping_list.lineItems', 'shopping_list_line_item')
            ->innerJoin('shopping_list_line_item.product', 'line_item_product')
            ->where($qb->expr()->eq('shopping_list', ':shopping_list'))
            ->setParameter('shopping_list', $shoppingList->getId())
            ->andWhere($qb->expr()->eq('line_item_product.type', $qb->expr()->literal(Product::TYPE_CONFIGURABLE)))
            ->setMaxResults(1)
            ->getQuery()
            ->getScalarResult();
    }
}
