<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\EntityListener\SegmentEntityListener;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class SegmentEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $productCache;

    /** @var SegmentEntityListener */
    private $entityListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productCache = $this->createMock(AbstractAdapter::class);

        $this->entityListener = new SegmentEntityListener($this->productCache);
    }

    public function testPreRemove()
    {
        $this->productCache
            ->expects($this->once())
            ->method('clear');

        $this->entityListener->preRemove(new Segment());
    }

    public function testPostPersist()
    {
        $this->productCache
            ->expects($this->once())
            ->method('clear');

        $this->entityListener->postPersist(new Segment());
    }

    public function testPreUpdate()
    {
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);
        $eventArgs->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn([1]);

        $this->productCache
            ->expects($this->once())
            ->method('clear');

        $this->entityListener->preUpdate(new Segment(), $eventArgs);
    }

    public function testPreUpdateWithoutChanges()
    {
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);
        $eventArgs->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn([]);

        $this->productCache
            ->expects($this->never())
            ->method('clear');

        $this->entityListener->preUpdate(new Segment(), $eventArgs);
    }
}
