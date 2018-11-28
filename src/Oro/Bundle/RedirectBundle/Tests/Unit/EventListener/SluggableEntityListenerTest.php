<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\EventListener\SluggableEntityListener;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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
        $this->configManager = $this->createMock(ConfigManager::class);

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
        $this->assertAttributeEmpty('sluggableEntities', $this->sluggableEntityListener);
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
        $this->assertAttributeEmpty('sluggableEntities', $this->sluggableEntityListener);
    }

    public function testPostPersistWithDisabledListener()
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $args->expects($this->never())
            ->method('getEntity');

        $this->configManager->expects($this->never())
            ->method('get');

        $this->messageFactory->expects($this->never())
            ->method('createMessage');

        $this->disableListener();
        $this->sluggableEntityListener->postPersist($args);
        $this->assertAttributeEmpty('sluggableEntities', $this->sluggableEntityListener);
    }

    public function testPostPersist()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityId = 1;

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);
        $args->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $this->sluggableEntityListener->postPersist($args);
        $this->assertAttributeEquals(
            [get_class($entity) => [true => [$entityId]]],
            'sluggableEntities',
            $this->sluggableEntityListener
        );
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
        $this->assertAttributeEmpty('sluggableEntities', $this->sluggableEntityListener);
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
        $this->assertAttributeEmpty('sluggableEntities', $this->sluggableEntityListener);
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
        $this->assertAttributeEmpty('sluggableEntities', $this->sluggableEntityListener);
    }

    public function testOnFlushChangedSlugWithChangedPrototypesIns()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityId = 1;
        $entity = $this->prepareSluggableEntity($event);
        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $sluggableEntities = [get_class($entity) => [true => [$entityId]]];

        $this->sluggableEntityListener->onFlush($event);

        $this->assertAttributeEquals(
            $sluggableEntities,
            'sluggableEntities',
            $this->sluggableEntityListener
        );
    }

    public function testOnFlushChangedSlugWithChangedPrototypesDel()
    {
        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $entityId = 1;

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

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$prototype]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $sluggableEntities = [get_class($entity) => [true => [$entityId]]];

        $this->sluggableEntityListener->onFlush($event);

        $this->assertAttributeEquals(
            $sluggableEntities,
            'sluggableEntities',
            $this->sluggableEntityListener
        );
    }

    public function testOnFlushWithDisabledListener()
    {
        $event = $this->createMock(OnFlushEventArgs::class);
        $event->expects($this->never())
            ->method('getEntityManager');

        $this->configManager->expects($this->never())
            ->method('get');

        $this->messageFactory->expects($this->never())
            ->method('createMessage');

        $this->disableListener();
        $this->sluggableEntityListener->onFlush($event);
        $this->assertAttributeEmpty('sluggableEntities', $this->sluggableEntityListener);
    }

    /**
     * @dataProvider slugPrototypeWithRedirectDataProvider
     */
    public function testPostFlushWithSlugPrototypeWithRedirect(bool $createRedirect, bool $expectedCreateRedirect)
    {
        $entityId = 1;
        $entity = new SluggableEntityStub();
        $entity->setId($entityId);
        $entity->setSlugPrototypesWithRedirect(new SlugPrototypesWithRedirect(
            new ArrayCollection([new LocalizedFallbackValue()]),
            $createRedirect
        ));

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $message = [
            'id' => [$entityId],
            'class' => get_class($entity),
            'createRedirect' => $createRedirect
        ];
        $this->messageFactory->expects($this->once())
            ->method('createMassMessage')
            ->with(get_class($entity), [$entityId], $expectedCreateRedirect)
            ->willReturn($message);

        $this->sluggableEntityListener
            ->postPersist(new LifecycleEventArgs($entity, $this->createMock(ObjectManager::class)));

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $message);

        $this->sluggableEntityListener->postFlush();
    }

    /**
     * @return array
     */
    public function slugPrototypeWithRedirectDataProvider(): array
    {
        return [
            'create redirect is true' => [
                'createRedirect' => true,
                'expectedCreateRedirect' => true
            ],
            'create redirect is false' => [
                'createRedirect' => false,
                'expectedCreateRedirect' => false
            ]
        ];
    }

    public function testPostFlushWithSlugPrototypeWithRedirectWithMultiple()
    {
        $entityWithoutRedirectId = 1;
        $entityWithoutRedirect = new SluggableEntityStub();
        $entityWithoutRedirect->setId($entityWithoutRedirectId);
        $entityWithoutRedirect->setSlugPrototypesWithRedirect(new SlugPrototypesWithRedirect(
            new ArrayCollection([new LocalizedFallbackValue()]),
            false
        ));

        $entityWithRedirectId = 1;
        $entityWithRedirect = new SluggableEntityStub();
        $entityWithRedirect->setId($entityWithoutRedirectId);
        $entityWithRedirect->setSlugPrototypesWithRedirect(new SlugPrototypesWithRedirect(
            new ArrayCollection([new LocalizedFallbackValue()]),
            true
        ));

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $messageWithoutRedirect = [
            'id' => [$entityWithoutRedirectId],
            'class' => get_class($entityWithoutRedirect),
            'createRedirect' => false
        ];

        $messageWithRedirect = [
            'id' => [$entityWithRedirectId],
            'class' => get_class($entityWithRedirect),
            'createRedirect' => true
        ];

        $this->messageFactory->expects($this->exactly(2))
            ->method('createMassMessage')
            ->withConsecutive(
                [get_class($entityWithoutRedirect), [$entityWithoutRedirectId]],
                [get_class($entityWithoutRedirect), [$entityWithRedirectId]]
            )
            ->willReturnOnConsecutiveCalls($messageWithoutRedirect, $messageWithRedirect);

        $this->sluggableEntityListener
            ->postPersist(new LifecycleEventArgs($entityWithoutRedirect, $this->createMock(ObjectManager::class)));

        $this->sluggableEntityListener
            ->postPersist(new LifecycleEventArgs($entityWithRedirect, $this->createMock(ObjectManager::class)));

        $this->messageProducer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $messageWithoutRedirect],
                [Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $messageWithRedirect]
            );

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

    protected function disableListener()
    {
        $this->assertInstanceOf(OptionalListenerInterface::class, $this->sluggableEntityListener);
        $this->sluggableEntityListener->setEnabled(false);
    }
}
