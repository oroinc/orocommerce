<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Handler\CollectionSortOrderHandler;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\EventListener\ContentNodeSortOrderListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Test\FormInterface;

class ContentNodeSortOrderListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private ContentNodeSortOrderListener $contentNodeSortOrderListener;

    protected function setUp(): void
    {
        $this->collectionSortOrderHandler = $this->createMock(CollectionSortOrderHandler::class);

        $this->contentNodeSortOrderListener = new ContentNodeSortOrderListener($this->collectionSortOrderHandler);
    }

    public function testOnFormAfterFlushWithoutSortOrder(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->with('contentVariants')
            ->willReturn([]);
        $event = $this->createMock(AfterFormProcessEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $this->collectionSortOrderHandler->expects(self::once())
            ->method('updateCollections')
            ->with([]);

        $this->contentNodeSortOrderListener->onFormAfterFlush($event);
    }

    public function testOnFormAfterFlushWithSortOrder(): void
    {
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        $segment = $this->createMock(Segment::class);
        $segment->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);
        $collectionSortOrderForm = $this->createMock(FormInterface::class);
        $collectionSortOrder = $this->createMock(CollectionSortOrder::class);
        $collectionSortOrderForm->expects($this->exactly(2))
            ->method('getData')
            ->willReturn([['data' => $collectionSortOrder]]);
        $productCollectionSegmentForm = $this->createMock(FormInterface::class);
        $productCollectionSegmentForm->expects($this->once())
            ->method('get')
            ->with('sortOrder')
            ->willReturn($collectionSortOrderForm);
        $productCollectionSegmentForm->expects($this->once())
            ->method('has')
            ->with('sortOrder')
            ->willReturn(true);
        $productCollectionSegmentForm->expects($this->once())
            ->method('getData')
            ->willReturn($segment);
        $contentVariantForm = $this->createMock(FormInterface::class);
        $contentVariantForm->expects($this->exactly(2))
            ->method('get')
            ->with('productCollectionSegment')
            ->willReturn($productCollectionSegmentForm);
        $contentVariantForm->expects($this->once())
            ->method('has')
            ->with('productCollectionSegment')
            ->willReturn(true);
        $contentNodeForm = $this->createMock(FormInterface::class);
        $contentNodeForm->expects($this->once())
            ->method('get')
            ->with('contentVariants')
            ->willReturn([$contentVariantForm]);
        $event = $this->createMock(AfterFormProcessEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($contentNodeForm);

        $this->collectionSortOrderHandler->expects(self::once())
            ->method('updateCollections')
            ->with([1 => ['segment' => $segment, 'sortOrders' => [0 => $collectionSortOrder]]]);

        $this->contentNodeSortOrderListener->onFormAfterFlush($event);
    }
}
