<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\EventListener\SluggableEntityListener;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SluggableEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageProducer;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var SluggableEntityListener
     */
    protected $sluggableEntityListener;

    protected function setUp()
    {
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sluggableEntityListener = new SluggableEntityListener(
            $this->messageFactory,
            $this->messageProducer,
            $this->configManager
        );
    }

    public function testPostPersistDisabledDirectUrl()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $args->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->sluggableEntityListener->postPersist($args);
        $this->assertAttributeEmpty('messages', $this->sluggableEntityListener);
    }

    public function testPostPersistNotSluggableEntity()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $entity = new \stdClass();
        $args->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->sluggableEntityListener->postPersist($args);
        $this->assertAttributeEmpty('messages', $this->sluggableEntityListener);
    }

    public function testPostPersist()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $args->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $message = ['class' => get_class($entity), 'id' => 1];
        $this->assertScheduleMessageCalled($entity, $message);

        $this->sluggableEntityListener->postPersist($args);
        $this->assertAttributeEquals([$message], 'messages', $this->sluggableEntityListener);
    }

    public function testOnFlushDisabledDirectUrl()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->prepareSluggableEntity($event);

        $this->sluggableEntityListener->onFlush($event);
        $this->assertAttributeEmpty('messages', $this->sluggableEntityListener);
    }

    public function testOnFlushNoChangedSlugs()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new LocalizedFallbackValue()]);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new LocalizedFallbackValue()]);
        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->sluggableEntityListener->onFlush($event);
        $this->assertAttributeEmpty('messages', $this->sluggableEntityListener);
    }

    public function testOnFlushChangedSlugWithoutChangedPrototypesUp()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([
                $entity,
                new LocalizedFallbackValue()
            ]);
        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->sluggableEntityListener->onFlush($event);
        $this->assertAttributeEmpty('messages', $this->sluggableEntityListener);
    }

    public function testOnFlushChangedSlugWithChangedPrototypesIns()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entity = $this->prepareSluggableEntity($event);

        $message = ['class' => get_class($entity), 'id' => 1];
        $this->assertScheduleMessageCalled($entity, $message);

        $this->sluggableEntityListener->onFlush($event);
        $this->assertAttributeEquals([$message], 'messages', $this->sluggableEntityListener);
    }

    public function testOnFlushChangedSlugWithChangedPrototypesDel()
    {
        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);

        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uow = $this->prepareUow($event, $entity);

        $prototype = new LocalizedFallbackValue();

        $entity->expects($this->once())
            ->method('hasSlugPrototype')
            ->with($prototype)
            ->willReturn(true);

        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$prototype]);

        $message = ['class' => get_class($entity), 'id' => 1];
        $this->assertScheduleMessageCalled($entity, $message);

        $this->sluggableEntityListener->onFlush($event);
        $this->assertAttributeEquals([$message], 'messages', $this->sluggableEntityListener);
    }

    public function testPostFlush()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $args->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $message = ['class' => get_class($entity), 'id' => 1];
        $this->assertScheduleMessageCalled($entity, $message);

        $this->sluggableEntityListener->postPersist($args);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $message);

        $this->sluggableEntityListener->postFlush();
    }

    /**
     * @param OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event
     * @return SluggableInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareSluggableEntity($event)
    {
        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);

        $uow  = $this->prepareUow($event, $entity);

        $prototype = new LocalizedFallbackValue();

        $entity->expects($this->once())
            ->method('hasSlugPrototype')
            ->with($prototype)
            ->willReturn(true);

        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$prototype]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        return $entity;
    }

    /**
     * @param OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event
     * @param SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity
     * @return UnitOfWork|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareUow($event, $entity)
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
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([
                $entity
            ]);

        return $uow;
    }

    /**
     * @param object $entity
     * @param array $message
     */
    private function assertScheduleMessageCalled($entity, array $message)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);
        $this->messageFactory->expects($this->once())
            ->method('createMessage')
            ->with($entity)
            ->willReturn($message);
    }
}
