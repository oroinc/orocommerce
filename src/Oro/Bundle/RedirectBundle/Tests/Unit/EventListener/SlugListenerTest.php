<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\RedirectBundle\Async\Topic\SyncSlugRedirectsTopic;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\EventListener\SlugListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\ReflectionUtil;

class SlugListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var SlugListener */
    private $listener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new SlugListener($this->messageProducer);
    }

    public function testOnFlushNoChangedSlugs(): void
    {
        $uow = $this->createMock(UnitOfWork::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->messageProducer->expects(self::never())
            ->method(self::anything());

        $this->listener->onFlush(new OnFlushEventArgs($em));
    }

    public function testOnFlushChangedSlugs(): void
    {
        $updatedSlug = new Slug();
        ReflectionUtil::setId($updatedSlug, 123);

        $uow = $this->createMock(UnitOfWork::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$updatedSlug]);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(SyncSlugRedirectsTopic::getName(), ['slugId' => $updatedSlug->getId()]);

        $this->listener->onFlush(new OnFlushEventArgs($em));
    }

    public function testOnFlushChangedSlugsWithDisabledListener(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method('getUnitOfWork');

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->listener->setEnabled(false);
        $this->listener->onFlush(new OnFlushEventArgs($em));
    }
}
