<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Oro\Bundle\InventoryBundle\EventListener\Frontend\ProductListUpcomingLabelListener;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ProductListUpcomingLabelListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductListUpcomingLabelListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductListUpcomingLabelListener();
    }

    public function testOnBuildQuery(): void
    {
        $query = $this->createMock(SearchQueryInterface::class);

        $query->expects(self::exactly(2))
            ->method('addSelect')
            ->withConsecutive(
                ['integer.is_upcoming'],
                ['datetime.availability_date']
            )
            ->willReturnSelf();

        $this->listener->onBuildQuery(new BuildQueryProductListEvent('test_list', $query));
    }

    public function testOnBuildResult(): void
    {
        $productData = [
            1 => [
                'id'                => 1,
                'is_upcoming'       => 1,
                'availability_date' => new \DateTime('now')
            ],
            2 => [
                'id'                => 2,
                'is_upcoming'       => 0,
                'availability_date' => ''
            ]
        ];
        $productView1 = $this->createMock(ProductView::class);
        $productView2 = $this->createMock(ProductView::class);
        $productViews = [1 => $productView1, 2 => $productView2];

        $productView1->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['is_upcoming', self::identicalTo(true)],
                ['availability_date', self::identicalTo($productData[1]['availability_date'])]
            );
        $productView2->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['is_upcoming', self::identicalTo(false)],
                ['availability_date', self::identicalTo(null)]
            );

        $this->listener->onBuildResult(new BuildResultProductListEvent('test_list', $productData, $productViews));
    }
}
