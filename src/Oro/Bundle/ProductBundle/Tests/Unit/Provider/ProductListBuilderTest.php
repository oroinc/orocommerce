<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\ProductBundle\Event\ProductListEventDispatcher;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ProductListBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $queryFactory;

    /** @var ProductListEventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ProductListBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->queryFactory = $this->createMock(QueryFactoryInterface::class);
        $this->eventDispatcher = $this->createMock(ProductListEventDispatcher::class);

        $this->builder = new ProductListBuilder($this->queryFactory, $this->eventDispatcher);
    }

    private function getProductView(int $id): ProductView
    {
        $productView = new ProductView();
        $productView->set('id', $id);

        return $productView;
    }

    public function testGetProductsByIds(): void
    {
        $productListType = 'test_list';
        $productIds = [3, 2, 1];
        $productView1 = $this->getProductView(1);
        $productView2 = $this->getProductView(2);

        $query = $this->createMock(SearchQueryInterface::class);
        $this->queryFactory->expects(self::once())
            ->method('create')
            ->with(['search_index' => 'website'])
            ->willReturn($query);
        $query->expects(self::once())
            ->method('setFrom')
            ->with('oro_product_WEBSITE_ID')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('addSelect')
            ->with('integer.system_entity_id as id')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('addWhere')
            ->with(Criteria::expr()->in('integer.system_entity_id', $productIds))
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('setMaxResults')
            ->with(Query::INFINITY)
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('execute')
            ->willReturn(
                [
                    new SearchResultItem('product', null, null, ['id' => 1, 'sku' => 'p1']),
                    new SearchResultItem('product', null, null, ['id' => 2, 'sku' => 'p2'])
                ]
            );

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    new BuildQueryProductListEvent($productListType, $query),
                    BuildQueryProductListEvent::NAME
                ],
                [
                    new BuildResultProductListEvent(
                        $productListType,
                        [1 => ['id' => 1, 'sku' => 'p1'], 2 => ['id' => 2, 'sku' => 'p2']],
                        [1 => $productView1, 2 => $productView2]
                    ),
                    BuildResultProductListEvent::NAME
                ]
            )
            ->willReturnArgument(0);

        self::assertEquals(
            [$productView2, $productView1],
            $this->builder->getProductsByIds($productListType, $productIds)
        );
    }

    public function testGetProductsWithoutInitializeQueryCallback(): void
    {
        $productListType = 'test_list';
        $productView1 = $this->getProductView(1);
        $productView2 = $this->getProductView(2);

        $query = $this->createMock(SearchQueryInterface::class);
        $this->queryFactory->expects(self::once())
            ->method('create')
            ->with(['search_index' => 'website'])
            ->willReturn($query);
        $query->expects(self::once())
            ->method('setFrom')
            ->with('oro_product_WEBSITE_ID')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('addSelect')
            ->with('integer.system_entity_id as id')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('execute')
            ->willReturn(
                [
                    new SearchResultItem('product', null, null, ['id' => 1, 'sku' => 'p1']),
                    new SearchResultItem('product', null, null, ['id' => 2, 'sku' => 'p2'])
                ]
            );

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    new BuildQueryProductListEvent($productListType, $query),
                    BuildQueryProductListEvent::NAME
                ],
                [
                    new BuildResultProductListEvent(
                        $productListType,
                        [1 => ['id' => 1, 'sku' => 'p1'], 2 => ['id' => 2, 'sku' => 'p2']],
                        [1 => $productView1, 2 => $productView2]
                    ),
                    BuildResultProductListEvent::NAME
                ]
            )
            ->willReturnArgument(0);

        self::assertEquals(
            [$productView1, $productView2],
            $this->builder->getProducts($productListType)
        );
    }

    public function testGetProductsWithInitializeQueryCallback(): void
    {
        $productListType = 'test_list';
        $productView1 = $this->getProductView(1);
        $productView2 = $this->getProductView(2);

        $query = $this->createMock(SearchQueryInterface::class);
        $this->queryFactory->expects(self::once())
            ->method('create')
            ->with(['search_index' => 'website'])
            ->willReturn($query);
        $query->expects(self::once())
            ->method('setFrom')
            ->with('oro_product_WEBSITE_ID')
            ->willReturnSelf();
        $query->expects(self::exactly(2))
            ->method('addSelect')
            ->withConsecutive(['integer.system_entity_id as id'], ['text.sku'])
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('execute')
            ->willReturn(
                [
                    new SearchResultItem('product', null, null, ['id' => 1, 'sku' => 'p1']),
                    new SearchResultItem('product', null, null, ['id' => 2, 'sku' => 'p2'])
                ]
            );

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    new BuildQueryProductListEvent($productListType, $query),
                    BuildQueryProductListEvent::NAME
                ],
                [
                    new BuildResultProductListEvent(
                        $productListType,
                        [1 => ['id' => 1, 'sku' => 'p1'], 2 => ['id' => 2, 'sku' => 'p2']],
                        [1 => $productView1, 2 => $productView2]
                    ),
                    BuildResultProductListEvent::NAME
                ]
            )
            ->willReturnArgument(0);

        self::assertEquals(
            [$productView1, $productView2],
            $this->builder->getProducts($productListType, function (SearchQueryInterface $query) {
                $query->addSelect('text.sku');
            })
        );
    }
}
