<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\RFPOperationListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Provider\QuickAddCollectionProvider;
use Oro\Bundle\RFPBundle\Event\FormSubmitCheckEvent;

class RFPOperationListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var QuickAddCollectionProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $collectionProvider;

    /**
     * @var RFPOperationListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->collectionProvider = $this->getMockBuilder(QuickAddCollectionProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new RFPOperationListener($this->collectionProvider);
    }

    public function testOnCopyPasteRFPFormSubmitCheckReturnsFalseEvent()
    {
        $this->collectionProvider->expects($this->once())
            ->method('processCopyPaste')
            ->willReturn([]);
        $event = new FormSubmitCheckEvent();
        $event->setShouldSubmitOnError(false);
        $this->listener->onCopyPasteRFPFormSubmitCheck($event);
        $this->assertFalse($event->isSubmitOnError());
    }

    public function testOnCopyPasteRFPFormSubmitCheckReturnsTrueEvent()
    {
        $collection = new QuickAddRowCollection();
        $row = new QuickAddRow(1, 'testSku', 2, 'item');
        $row->setProduct(new Product());
        $collection->add($row);
        $this->collectionProvider->expects($this->once())
            ->method('processCopyPaste')
            ->willReturn($collection);
        $event = new FormSubmitCheckEvent();
        $event->setShouldSubmitOnError(false);
        $this->listener->onCopyPasteRFPFormSubmitCheck($event);
        $this->assertTrue($event->isSubmitOnError());
    }

    public function testOnQuickAddImportRFPFormSubmitCheckReturnsFalseEvent()
    {
        $this->collectionProvider->expects($this->once())
            ->method('processImport')
            ->willReturn([]);
        $event = new FormSubmitCheckEvent();
        $event->setShouldSubmitOnError(false);
        $this->listener->onQuickAddImportRFPFormSubmitCheck($event);
        $this->assertFalse($event->isSubmitOnError());
    }

    public function testOnQuickAddImportRFPFormSubmitCheckTrueEvent()
    {
        $collection = new QuickAddRowCollection();
        $row = new QuickAddRow(1, 'testSku', 2, 'item');
        $row->setProduct(new Product());
        $collection->add($row);
        $this->collectionProvider->expects($this->once())
            ->method('processImport')
            ->willReturn($collection);
        $event = new FormSubmitCheckEvent();
        $event->setShouldSubmitOnError(false);
        $this->listener->onQuickAddImportRFPFormSubmitCheck($event);
        $this->assertTrue($event->isSubmitOnError());
    }
}
