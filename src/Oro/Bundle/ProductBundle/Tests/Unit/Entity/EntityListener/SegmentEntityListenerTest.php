<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\EntityListener\SegmentEntityListener;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Cache\Layout\DataProviderCacheCleaner;

class SegmentEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DataProviderCacheCleaner|\PHPUnit_Framework_MockObject_MockObject */
    private $productCacheCleaner;

    /** @var SegmentEntityListener */
    private $entityListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->productCacheCleaner = $this->createMock(DataProviderCacheCleaner::class);

        $this->entityListener = new SegmentEntityListener($this->productCacheCleaner);
    }

    public function testPreRemove()
    {
        $this->productCacheCleaner
            ->expects($this->once())
            ->method('clearCache');

        $this->entityListener->preRemove(new Segment());
    }

    public function testPostPersist()
    {
        $this->productCacheCleaner
            ->expects($this->once())
            ->method('clearCache');

        $this->entityListener->postPersist(new Segment());
    }

    public function testPreUpdate()
    {
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);
        $eventArgs->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn([1]);

        $this->productCacheCleaner
            ->expects($this->once())
            ->method('clearCache');

        $this->entityListener->preUpdate(new Segment(), $eventArgs);
    }

    public function testPreUpdateWithoutChanges()
    {
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);
        $eventArgs->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn([]);

        $this->productCacheCleaner
            ->expects($this->never())
            ->method('clearCache');

        $this->entityListener->preUpdate(new Segment(), $eventArgs);
    }
}
