<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\ProductBundle\Model\ProductView;

class BuildResultProductListEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $productView1 = new ProductView();
        $productView1->set('id', 1);
        $productView2 = new ProductView();
        $productView2->set('id', 2);

        $productListType = 'test_list';
        $productData = [1 => ['id' => 1], 2 => ['id' => 2]];
        $productViews = [1 => $productView1, 2 => $productView2];

        $event = new BuildResultProductListEvent($productListType, $productData, $productViews);

        self::assertSame($productListType, $event->getProductListType());
        self::assertSame($productData, $event->getProductData());
        self::assertSame($productViews, $event->getProductViews());
        self::assertSame($productView1, $event->getProductView(1));
        self::assertSame($productView2, $event->getProductView(2));
    }

    public function testGetProductViewWhenNoRequestedView(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A product view does not exist. Product ID: 1.');

        $event = new BuildResultProductListEvent('test_list', [], []);
        $event->getProductView(1);
    }
}
