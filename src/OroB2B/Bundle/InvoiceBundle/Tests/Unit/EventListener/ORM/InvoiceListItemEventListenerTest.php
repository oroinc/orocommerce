<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreFlushEventArgs;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use OroB2B\Bundle\InvoiceBundle\EventListener\ORM\InvoiceListItemEventListener;

class InvoiceListItemEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var InvoiceListItemEventListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new InvoiceListItemEventListener();
    }

    public function testPreFlush()
    {
        /** @var Invoice $invoice */
        $invoice = $this->getEntity('OroB2B\Bundle\InvoiceBundle\Entity\Invoice', [
            'id' => 1,
            'updatedAt' => new \DateTime()
        ]);
        $lineItem = new InvoiceLineItem();
        $lineItem->setInvoice($invoice);

        $manager = $this->getObjectManager();

        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($metadata, $invoice);

        $manager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $manager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $event = new PreFlushEventArgs($manager);

        $this->listener->preFlush($lineItem, $event);
        $this->assertNull($invoice->getUpdatedAt());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getObjectManager()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
