<?php

namespace Oro\Bundle\InvoiceBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\InvoiceBundle\Doctrine\ORM\SimpleInvoiceNumberGenerator;
use Oro\Bundle\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\InvoiceBundle\EventListener\ORM\InvoiceEventListener;

class InvoiceEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InvoiceEventListener
     */
    protected $invoiceEventListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->invoiceEventListener = new InvoiceEventListener();
    }

    public function testPostPersist()
    {
        $invoice = new Invoice();
        $manager = $this->getObjectManager();

        $event = new LifecycleEventArgs($invoice, $manager);
        $generator = $this->getInvoiceNumberGenerator();

        $generator->expects($this->once())
            ->method('generate')
            ->willReturn(5);

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($invoice, ['invoiceNumber' => [null, 5]]);

        $manager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->invoiceEventListener->setInvoiceNumberGenerator($generator);
        $this->invoiceEventListener->postPersist($invoice, $event);
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SimpleInvoiceNumberGenerator
     */
    protected function getInvoiceNumberGenerator()
    {
        return $this->getMockBuilder('Oro\Bundle\InvoiceBundle\Doctrine\ORM\SimpleInvoiceNumberGenerator')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
