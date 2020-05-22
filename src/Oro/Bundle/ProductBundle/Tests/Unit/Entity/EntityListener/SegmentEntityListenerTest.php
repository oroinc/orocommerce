<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\EntityListener\SegmentEntityListener;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productCache;

    /** @var SegmentEntityListener */
    private $entityListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productCache = $this->createMock(CacheProvider::class);

        $this->entityListener = new SegmentEntityListener($this->productCache);
    }

    public function testPreRemove()
    {
        $this->productCache
            ->expects($this->once())
            ->method('deleteAll');

        $this->entityListener->preRemove(new Segment());
    }

    public function testPostPersist()
    {
        $this->productCache
            ->expects($this->once())
            ->method('deleteAll');

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
            ->method('deleteAll');

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
            ->method('deleteAll');

        $this->entityListener->preUpdate(new Segment(), $eventArgs);
    }
}
