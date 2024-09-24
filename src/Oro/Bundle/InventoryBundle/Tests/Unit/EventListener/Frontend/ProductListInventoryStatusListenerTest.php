<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\ProductListInventoryStatusListener;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use PHPUnit\Framework\TestCase;

class ProductListInventoryStatusListenerTest extends TestCase
{
    private ProductListInventoryStatusListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);
        $enumOptionsProvider
            ->expects(self::any())
            ->method('getEnumInternalChoices')
            ->with('prod_inventory_status')
            ->willReturn(['in_stock' => 'In Stock']);

        $this->listener = new ProductListInventoryStatusListener($enumOptionsProvider);
    }

    public function testOnBuildQuery(): void
    {
        $query = $this->createMock(SearchQueryInterface::class);

        $query->expects(self::exactly(1))
            ->method('addSelect')
            ->withConsecutive(
                ['text.inv_status as inventory_status'],
            )
            ->willReturnSelf();

        $this->listener->onBuildQuery(new BuildQueryProductListEvent('test_list', $query));
    }

    public function testOnBuildResult(): void
    {
        $productData = [
            1 => [
                'id' => 1,
                'inventory_status' => 'in_stock',
            ],
            2 => [
                'id' => 2,
                'inventory_status' => 'out_of_stock',
            ],
            3 => [
                'id' => 3,
                'inventory_status' => '',
            ],
            4 => [
                'id' => 4,
                'inventory_status' => null,
            ],
            5 => [
                'id' => 5,
            ],
        ];
        $productView1 = $this->createMock(ProductView::class);
        $productView2 = $this->createMock(ProductView::class);
        $productView3 = $this->createMock(ProductView::class);
        $productView4 = $this->createMock(ProductView::class);
        $productView5 = $this->createMock(ProductView::class);
        $productViews = [
            1 => $productView1,
            2 => $productView2,
            3 => $productView3,
            4 => $productView4,
            5 => $productView5,
        ];

        $productView1->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['inventory_status', self::identicalTo('in_stock')],
                ['inventory_status_label', self::identicalTo('In Stock')],
            );
        $productView2->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['inventory_status', self::identicalTo('out_of_stock')],
                ['inventory_status_label', self::identicalTo('out_of_stock')],
            );
        $productView3->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['inventory_status', self::identicalTo('')],
                ['inventory_status_label', self::identicalTo('')],
            );
        $productView4->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['inventory_status', self::identicalTo(null)],
                ['inventory_status_label', self::identicalTo(null)],
            );
        $productView5->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['inventory_status', self::identicalTo(null)],
                ['inventory_status_label', self::identicalTo(null)],
            );

        $this->listener->onBuildResult(new BuildResultProductListEvent('test_list', $productData, $productViews));
    }
}
