<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\CatalogBundle\EventListener\ProductsChangeRelationListener;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductsChangeRelationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $unitOfWork;

    /** @var OnFlushEventArgs */
    private $onFlushEvent;

    /** @var ProductsChangeRelationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $this->onFlushEvent = new OnFlushEventArgs($em);

        $this->listener = new ProductsChangeRelationListener($this->eventDispatcher);
    }

    public function testOnFlush()
    {
        $category = $this->createMock(Category::class);

        $product1 = new Product();
        $product1->setCategory($category);

        $this->unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$product1]);

        $this->unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(['category' => $category]);

        $event = new ProductsChangeRelationEvent([$product1]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ProductsChangeRelationEvent::NAME);

        $this->listener->onFlush($this->onFlushEvent);
    }

    public function testOnFlushNoAnyEntityChanged()
    {
        $this->unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn(null);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        $this->listener->onFlush($this->onFlushEvent);
    }

    public function testOnFlushNoAnyProductChanged()
    {
        $category = new Category();

        $this->unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$category]);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        $this->listener->onFlush($this->onFlushEvent);
    }

    public function testOnFlushNoProductCategoryChanged()
    {
        $category = $this->createMock(Category::class);

        $product1 = new Product();
        $product1->setCategory($category);

        $this->unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$product1]);

        $this->unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(['update' => '2010-10-10 10:10:10']);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->listener->onFlush($this->onFlushEvent);
    }
}
