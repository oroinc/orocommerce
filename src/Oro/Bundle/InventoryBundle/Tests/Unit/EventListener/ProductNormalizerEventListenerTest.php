<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\ProductNormalizerEventListener;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductNormalizerEventListener */
    protected $listener;

    /** @var UpcomingProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $productUpcomingProvider;

    protected function setUp(): void
    {
        $this->productUpcomingProvider = $this->createMock(UpcomingProductProvider::class);
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
