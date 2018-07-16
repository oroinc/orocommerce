<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\ProductNormalizerEventListener;
use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var  ProductNormalizerEventListener */
    protected $listener;

    /** @var  ProductUpcomingProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $productUpcomingProvider;

    protected function setUp()
    {
        $this->productUpcomingProvider = $this->createMock(ProductUpcomingProvider::class);
        $this->listener = new ProductNormalizerEventListener($this->productUpcomingProvider);
    }

    public function testNormalize()
    {
        $product = $this->createMock(Product::class);
        $event = new ProductNormalizerEvent($product, []);

        $this->productUpcomingProvider->expects($this->once())->method('isUpcoming')->with($product)
            ->willReturn(true);

        $date = new \DateTime('tomorrow', new \DateTimeZone('UTC'));
        $this->productUpcomingProvider->expects($this->once())->method('getAvailabilityDate')
            ->willReturn($date);

        $this->listener->normalize($event);

        $this->assertEquals([
            'isUpcoming' => '1',
            'availability_date' => $date->format('Y-m-d\TH:i:sO')
        ], $event->getPlainData());
    }

    public function testNormalizeWithoutUpcomingFlag()
    {
        $product = $this->createMock(Product::class);
        $event = new ProductNormalizerEvent($product, []);

        $this->productUpcomingProvider->expects($this->once())->method('isUpcoming')->with($product)
            ->willReturn(false);

        $this->productUpcomingProvider->expects($this->never())->method('getAvailabilityDate');


        $this->listener->normalize($event);

        $this->assertEquals([], $event->getPlainData());
    }

    public function testNormalizeUnsupported()
    {
        $event = new ProductNormalizerEvent($this->createMock(Product::class), [], ['fieldName' => 'product']);
        $this->productUpcomingProvider->expects($this->never())->method('isUpcoming');
        $this->listener->normalize($event);
        $this->assertEmpty($event->getPlainData());
    }
}
