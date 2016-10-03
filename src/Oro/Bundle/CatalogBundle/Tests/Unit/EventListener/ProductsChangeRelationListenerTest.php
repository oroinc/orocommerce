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
    public function testOnFlush()
    {
        $emMock = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $unitOfWorkMock = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $emMock->expects($this->once())->method('getUnitOfWork')->willReturn($unitOfWorkMock);

        $collection = new PersistentCollection($emMock, Category::class, new ArrayCollection([]));
        $mapping = ['inversedBy' => 'Product', 'fieldName' => Category::FIELD_PRODUCTS];
        $collection->setOwner(new Category(), $mapping);
        $collection->setDirty(true);
        $collection->setInitialized(true);
        $product1 = new Product();
        $product2 = new Product();
        $collection->add($product1);
        $collection->add($product2);

        $unitOfWorkMock->expects($this->once())->method('getScheduledCollectionUpdates')->willReturn([$collection]);

        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $listener = new ProductsChangeRelationListener($eventDispatcherMock);
        $event = new ProductsChangeRelationEvent([$product1, $product2]);
        $eventDispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with(ProductsChangeRelationEvent::NAME, $event);
        $onFlushEvent = new OnFlushEventArgs($emMock);
        $listener->onFlush($onFlushEvent);
    }
}
