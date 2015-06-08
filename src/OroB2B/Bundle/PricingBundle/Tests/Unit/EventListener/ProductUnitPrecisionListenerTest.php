<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;
use OroB2B\Bundle\PricingBundle\Event\ProductPricesRemoveBefore;
use OroB2B\Bundle\PricingBundle\EventListener\ProductUnitPrecisionListener;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitPrecisionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ProductUnitPrecisionListener
     */
    protected $listener;

    /**
     * @var string
     */
    protected $productPriceClass;

    protected function setUp()
    {
        $this->productPriceClass = 'stdClass';
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->listener = new ProductUnitPrecisionListener();
        $this->listener->setEventDispatcher($this->eventDispatcher);
        $this->listener->setProductPriceClass($this->productPriceClass);
    }

    public function testPostRemoveInvalidEntity()
    {
        $entity = new \stdClass();
        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $event->expects($this->never())
            ->method('getEntityManager');

        $this->eventDispatcher->expects($this->never())
            ->method($this->anything());

        $this->listener->postRemove($event);
    }

    public function testPostRemove()
    {
        $entity = new ProductUnitPrecision();
        $product = new Product();
        $unit = new ProductUnit();
        $entity->setProduct($product)
            ->setUnit($unit);

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        $repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('deleteByProductUnit')
            ->with($product, $unit);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->productPriceClass)
            ->will($this->returnValue($repository));

        $event->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(
                ProductPricesRemoveBefore::NAME,
                $this->isInstanceOf('OroB2B\Bundle\PricingBundle\Event\ProductPricesRemoveBefore')
            );
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(
                ProductPricesRemoveAfter::NAME,
                $this->isInstanceOf('OroB2B\Bundle\PricingBundle\Event\ProductPricesRemoveAfter')
            );

        $this->listener->postRemove($event);
    }
}
