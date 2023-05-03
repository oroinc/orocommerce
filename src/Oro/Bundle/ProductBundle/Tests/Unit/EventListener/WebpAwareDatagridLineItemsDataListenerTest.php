<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\WebpAwareDatagridLineItemsDataListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductImageStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;

class WebpAwareDatagridLineItemsDataListenerTest extends \PHPUnit\Framework\TestCase
{
    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private WebpAwareDatagridLineItemsDataListener $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);

        $this->listener = new WebpAwareDatagridLineItemsDataListener($this->attachmentManager);
    }

    public function testOnLineItemDataWebpStrategyNotEnabledIfSupported(): void
    {
        $event = new DatagridLineItemsDataEvent([], [], $this->createMock(DatagridInterface::class), []);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(false);

        $this->listener->onLineItemData($event);

        self::assertEquals([], $event->getDataForAllLineItems());
    }

    public function testOnLineItemData(): void
    {
        $productImage = new ProductImageStub();
        $productImage->setImage((new File())->setFilename('image1.jpg'));
        $productImage->addType('listing');

        $productWithImage = (new ProductStub())
            ->setId(1)
            ->setSku('p1')
            ->addImage($productImage);

        $product = (new ProductStub())
            ->setId(2)
            ->setSku('p2');

        $lineItems = [
            1 => (new ProductLineItemStub(1)),
            2 => (new ProductLineItemStub(2))->setProduct($productWithImage),
            3 => (new ProductLineItemStub(3))->setProduct($product),
        ];

        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(DatagridInterface::class), []);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);
        $this->attachmentManager
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($productImage->getImage(), 'product_small', 'webp')
            ->willReturn('image1.jpg.webp');

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                2 => [
                    'imageWebp' => 'image1.jpg.webp',
                ],
                3 => [
                    'imageWebp' => '',
                ],
            ],
            $event->getDataForAllLineItems()
        );
    }
}
