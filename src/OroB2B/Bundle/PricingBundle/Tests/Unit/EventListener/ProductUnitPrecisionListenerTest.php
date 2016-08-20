<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveBefore;
use Oro\Bundle\PricingBundle\EventListener\ProductUnitPrecisionListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitPrecisionListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    
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

    public function testPostRemoveWithExistingProduct()
    {
        $entity = new ProductUnitPrecision();
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 1]);
        $unit = new ProductUnit();
        $entity->setProduct($product)
            ->setUnit($unit);

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        $repository = $this->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
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
                $this->isInstanceOf('Oro\Bundle\PricingBundle\Event\ProductPricesRemoveBefore')
            );
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(
                ProductPricesRemoveAfter::NAME,
                $this->isInstanceOf('Oro\Bundle\PricingBundle\Event\ProductPricesRemoveAfter')
            );

        $this->listener->postRemove($event);
    }

    public function testPostRemoveWithDeletedProduct()
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

        $repository = $this->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->never())
            ->method('deleteByProductUnit');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->never())
            ->method('getRepository');

        $event->expects($this->never())
            ->method('getEntityManager');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->listener->postRemove($event);
    }
}
