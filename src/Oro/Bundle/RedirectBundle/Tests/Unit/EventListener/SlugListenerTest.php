<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\EventListener\SlugListener;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class SlugListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageProducer;

    /**
     * @var SlugListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new SlugListener($this->registry, $this->messageProducer);
    }

    public function testOnFlushNoChangedSlugs()
    {
        /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $event = new OnFlushEventArgs($em);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->messageProducer->expects($this->never())
            ->method($this->anything());

        $this->listener->onFlush($event);
    }

    public function testOnFlushChangedSlugs()
    {
        $updatedSlug = $this->getEntity(Slug::class, ['id' => 123]);

        /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $event = new OnFlushEventArgs($em);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$updatedSlug]);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(
                Topics::SYNC_SLUG_REDIRECTS,
                new Message(['slugId' => $updatedSlug->getId()])
            );

        $this->listener->onFlush($event);
    }

    public function testOnFlushChangedSlugsWithDisabledListener()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('getUnitOfWork');

        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->disableListener();
        $this->listener->onFlush(new OnFlushEventArgs($em));
    }

    protected function disableListener()
    {
        $this->assertInstanceOf(OptionalListenerInterface::class, $this->listener);
        $this->listener->setEnabled(false);
    }
}
