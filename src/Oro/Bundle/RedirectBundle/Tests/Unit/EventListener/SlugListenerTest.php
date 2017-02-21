<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Bundle\RedirectBundle\EventListener\SlugListener;

class SlugListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageProducer;

    /**
     * @var SlugListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new SlugListener($this->registry, $this->messageProducer);
    }

    public function testOnFlushNoChangedSlugs()
    {
        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
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

        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
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
}
