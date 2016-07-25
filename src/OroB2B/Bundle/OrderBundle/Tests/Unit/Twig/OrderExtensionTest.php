<?php

namespace OroB2B\src\OroB2B\Bundle\OrderBundle\Tests\Unit\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use OroB2B\Bundle\OrderBundle\Twig\OrderExtension;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

class OrderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var SourceDocumentFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sourceDocumentFormatter;

    /**
     * @var PaymentStatusProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentStatusProvider;

    /**
     * @var OrderExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->registry = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceDocumentFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\OrderBundle\Formatter\SourceDocumentFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentStatusProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new OrderExtension(
            $this->registry,
            $this->sourceDocumentFormatter,
            $this->paymentStatusProvider
        );
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFunction('get_payment_status_label', [$this->extension, 'getPaymentStatusLabel'])
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(OrderExtension::NAME, $this->extension->getName());
    }

    public function testGetPaymentStatusLabel()
    {
        $order_id = 1;
        $repository = $this->getMock('\Doctrine\Common\Persistence\ObjectRepository');
        $manager = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');
        $order = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\Order');
        $this->registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BOrderBundle:Order')
            ->willReturn($manager);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BOrderBundle:Order')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('find')
            ->with($order_id)
            ->willReturn($order);

        $this->extension->getPaymentStatusLabel($order_id);
    }
}
