<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\InvoiceBundle\Doctrine\ORM\SimpleInvoiceNumberGenerator;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\EventListener\ORM\InvoiceEventListener;
use OroB2B\Bundle\PricingBundle\Model\LineItemsSubtotal;
use OroB2B\Bundle\PricingBundle\Provider\LineItemsSubtotalProvider;

class InvoiceEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InvoiceEventListener
     */
    protected $invoiceEventListener;

    protected function setUp()
    {
        $this->invoiceEventListener = new InvoiceEventListener();
    }

    public function testPrePersist()
    {
        $subtotal = new LineItemsSubtotal();
        $subtotal->setAmount(100);
        $this->invoiceEventListener->setLineItemsSubtotalProvider($this->getSubtotalProvider($subtotal));
        $invoice = new Invoice();

        $this->invoiceEventListener->prePersist($invoice);
        $this->assertSame($subtotal->getAmount(), $invoice->getSubtotal());
        $this->assertInstanceOf('\DateTime', $invoice->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $invoice->getCreatedAt());
    }

    public function testPreUpdate()
    {
        $subtotal = new LineItemsSubtotal();
        $subtotal->setAmount(150);
        $this->invoiceEventListener->setLineItemsSubtotalProvider($this->getSubtotalProvider($subtotal));

        $invoice = new Invoice();
        $previousUpdated = new \DateTime('-1 day');
        $invoice->setUpdatedAt($previousUpdated)
            ->setSubtotal(100);

        $this->invoiceEventListener->preUpdate($invoice);

        $this->assertTrue($invoice->getUpdatedAt() > $previousUpdated);
        $this->assertSame(150, $invoice->getSubtotal());
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
     * @param LineItemsSubtotal $subtotal
     * @return LineItemsSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSubtotalProvider(LineItemsSubtotal $subtotal)
    {
        $provider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\LineItemsSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        return $provider;
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
        return $this->getMockBuilder('OroB2B\Bundle\InvoiceBundle\Doctrine\ORM\SimpleInvoiceNumberGenerator')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
