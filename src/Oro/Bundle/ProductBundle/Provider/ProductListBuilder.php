<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\ProductBundle\Event\ProductListEventDispatcher;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

/**
 * Builds the list of ProductView objects to render different kind of storefront product lists.
 */
class ProductListBuilder
{
    private QueryFactoryInterface $queryFactory;
    private ProductListEventDispatcher $eventDispatcher;

    public function __construct(
        QueryFactoryInterface $queryFactory,
        ProductListEventDispatcher $eventDispatcher
    ) {
        $this->queryFactory = $queryFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $productListType
     * @param int[]  $productIds
     *
     * @return ProductView[]
     */
    public function getProductsByIds(string $productListType, array $productIds): array
    {
        $loadedProducts = $this->loadProducts(
            $productListType,
            static function (SearchQueryInterface $query) use ($productIds) {
                $query
                    ->addWhere(Criteria::expr()->in('integer.system_entity_id', $productIds))
                    ->setMaxResults(Query::INFINITY);
            }
        );

        // the order of the returned products should be the same as the order of the requested product IDs
        $products = [];
        foreach ($productIds as $productId) {
            if (isset($loadedProducts[$productId])) {
                $products[] = $loadedProducts[$productId];
            }
        }

        return $products;
    }

    /**
     * @param string        $productListType
     * @param callable|null $initializeQueryCallback function (SearchQueryInterface $query): void
     *
     * @return ProductView[]
     */
    public function getProducts(string $productListType, callable $initializeQueryCallback = null): array
    {
        return array_values($this->loadProducts($productListType, $initializeQueryCallback));
    }

    /**
     * @param string        $productListType
     * @param callable|null $initializeQueryCallback function (SearchQueryInterface $query): void
     *
     * @return ProductView[] [product id => product view, ...]
     */
    private function loadProducts(string $productListType, callable $initializeQueryCallback = null): array
    {
        $query = $this->queryFactory->create(['search_index' => 'website'])
            ->setFrom('oro_product_WEBSITE_ID')
            ->addSelect('integer.system_entity_id as id');
        if (null !== $initializeQueryCallback) {
            $initializeQueryCallback($query);
        }
        $this->eventDispatcher->dispatch(
            new BuildQueryProductListEvent($productListType, $query),
            BuildQueryProductListEvent::NAME
        );

        $items = $query->execute();

        $productData = [];
        $productViews = [];
        foreach ($items as $item) {
            $product = $item->getSelectedData();
            $productId = $product['id'];
            $productData[$productId] = $product;

            $productView = new ProductView();
            $productView->set('id', $productId);
            $productViews[$productId] = $productView;
        }
        $this->eventDispatcher->dispatch(
            new BuildResultProductListEvent($productListType, $productData, $productViews),
            BuildResultProductListEvent::NAME
        );

        return $productViews;
    }
}
