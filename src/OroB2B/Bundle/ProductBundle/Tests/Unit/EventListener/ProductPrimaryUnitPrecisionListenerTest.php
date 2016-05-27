<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\ProductBundle\EventListener\ProductPrimaryUnitPrecisionListener;

class ProductPrimaryUnitPrecisionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var ProductPrimaryUnitPrecisionListener
     */
    protected $productPrimaryUnitPrecisionListener;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productPrimaryUnitPrecisionListener =
            new ProductPrimaryUnitPrecisionListener($this->doctrineHelper, $this->session);
    }

    public function testUpdateProductUnitPrecisionRelation()
    {
        $unit = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\ProductUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $unit->expects($this->once())
            ->method('getCode')
            ->will($this->returnValue('item'));

        $product = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Product')
            ->disableOriginalConstructor()
            ->getMock();

        $entity = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision')
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())
            ->method('getProduct')
            ->will($this->returnValue($product));
        $entity->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));
        $entity->expects($this->once())
            ->method('getUnit')
            ->will($this->returnValue($unit));

        $this->session->expects($this->once())
            ->method('get')
            ->will($this->returnValue('item'));

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($product, [
                'primaryUnitPrecisionId' => [
                    null,
                    1
                ]
            ]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $args = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $args->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->productPrimaryUnitPrecisionListener->postPersist($args);
    }
}
