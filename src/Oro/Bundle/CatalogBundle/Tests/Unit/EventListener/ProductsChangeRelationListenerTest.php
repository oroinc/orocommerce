<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\CatalogBundle\EventListener\ProductsChangeRelationListener;
use Oro\Bundle\ProductBundle\Entity\Product;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductsChangeRelationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitOfWork;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ProductsChangeRelationListener
     */
    protected $listener;

    /**
     * @var OnFlushEventArgs
     */
    protected $onFlushEvent;

    protected function setUp()
    {
        $this->em = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->getMock();
        $this->unitOfWork = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
        $this->eventDispatcher = $this
            ->getMockBuilder(EventDispatcherInterface::class)
            ->getMock();

        $this->listener = new ProductsChangeRelationListener($this->eventDispatcher);
        $this->onFlushEvent = new OnFlushEventArgs($this->em);
    }

    public function testOnFlush()
    {
        $collection = new PersistentCollection($this->em, Category::class, new ArrayCollection([]));
        $mapping = ['inversedBy' => 'Product', 'fieldName' => Category::FIELD_PRODUCTS];
        $collection->setOwner(new Category(), $mapping);
        $collection->setDirty(true);
        $collection->setInitialized(true);
        $product1 = new Product();
        $product2 = new Product();
        $collection->add($product1);
        $collection->add($product2);

        $this->unitOfWork->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([$collection]);

        $event = new ProductsChangeRelationEvent([$product1, $product2]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ProductsChangeRelationEvent::NAME, $event);

        $this->listener->onFlush($this->onFlushEvent);
    }

    public function testOnFlushWrongCollection()
    {
        $this->unitOfWork->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([new ArrayCollection()]);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        $this->listener->onFlush($this->onFlushEvent);
    }

    public function testOnFlushNotDirty()
    {
        $collection = new PersistentCollection($this->em, Category::class, new ArrayCollection([]));
        $collection->setDirty(false);
        $this->unitOfWork->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([$collection]);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        $this->listener->onFlush($this->onFlushEvent);
    }

    public function testOnFlushNotInitialized()
    {
        $collection = new PersistentCollection($this->em, Category::class, new ArrayCollection([]));
        $collection->setInitialized(false);
        $this->unitOfWork->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([$collection]);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        $this->listener->onFlush($this->onFlushEvent);
    }

    public function testOnFlushWrongMapping()
    {
        $collection = new PersistentCollection($this->em, Category::class, new ArrayCollection([]));
        $collection->setDirty(true);
        $mapping = ['inversedBy' => 'Product', 'fieldName' => 'randomField'];
        $collection->setOwner(new Category(), $mapping);
        $this->unitOfWork->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([$collection]);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        $this->listener->onFlush($this->onFlushEvent);
    }
}
