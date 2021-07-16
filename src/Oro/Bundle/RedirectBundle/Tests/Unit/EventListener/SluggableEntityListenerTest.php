<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\EventListener\SluggableEntityListener;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SluggableEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MessageFactoryInterface|MockObject */
    protected $messageFactory;

    /** @var MessageProducerInterface|MockObject */
    protected $messageProducer;

    /** @var ConfigManager|MockObject */
    protected $configManager;

    /** @var SluggableEntityListener */
    protected $sluggableEntityListener;

    protected function setUp(): void
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
        /** @var LifecycleEventArgs|MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)->disableOriginalConstructor()->getMock();

        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $args->expects(static::once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        // assertions
        $this->messageFactory->expects(static::never())->method('createMassMessage');
        $this->messageProducer->expects(static::never())->method('send');

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testPostPersistNotSluggableEntity()
    {
        /** @var LifecycleEventArgs|MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects(static::any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $entity = new \stdClass();
        $args->expects(static::once())
            ->method('getEntity')
            ->willReturn($entity);

        // assertions
        $this->messageFactory->expects(static::never())->method('createMassMessage');
        $this->messageProducer->expects(static::never())->method('send');

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testPostPersistWithDisabledListener()
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $args->expects(static::never())->method('getEntity');
        $this->configManager->expects(static::never())->method('get');
        $this->messageFactory->expects(static::never())->method('createMessage');

        $this->assertAndDisableListener();

        // assertions
        $this->messageFactory->expects(static::never())->method('createMassMessage');
        $this->messageProducer->expects(static::never())->method('send');

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testPostPersist()
    {
        /** @var LifecycleEventArgs|MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)->disableOriginalConstructor()->getMock();

        $entityId = 1;

        /** @var SluggableInterface|MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects(static::once())->method('getId')->willReturn($entityId);
        $args->expects(static::once())->method('getEntity')->willReturn($entity);

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        // assertions
        $message = ['id' => [$entityId], 'class' => \get_class($entity), 'createRedirect' => true];

        $this->messageFactory->expects(static::once())
            ->method('createMassMessage')
            ->with(\get_class($entity), [$entityId], true)
            ->willReturn($message);

        $this->messageProducer->expects(static::once())
            ->method('send')
            ->with(Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $message);

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testPostPersistWithDraft()
    {
        /** @var LifecycleEventArgs|MockObject $args **/
        $args = $this->createMock(LifecycleEventArgs::class);

        $entity = $this->getEntity(Page::class, ['id' => 1, 'draftUuid' => 42]);
        $args->expects(static::once())->method('getEntity')->willReturn($entity);

        // assertions
        $this->configManager->expects(static::never())->method('get');
        $this->messageFactory->expects(static::never())->method('createMassMessage');
        $this->messageProducer->expects(static::never())->method('send');

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushDisabledDirectUrl()
    {
        /** @var OnFlushEventArgs|MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->prepareSluggableEntity($event);

        // assertions
        $this->messageFactory->expects(static::never())->method('createMassMessage');
        $this->messageProducer->expects(static::never())->method('send');

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushNoChangedSlugs()
    {
        /** @var OnFlushEventArgs|MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects(static::any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        /** @var UnitOfWork|MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(static::any())->method('getUnitOfWork')->willReturn($uow);
        $event->expects(static::any())->method('getEntityManager')->willReturn($em);
        $uow->expects(static::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new LocalizedFallbackValue()]);
        $uow->expects(static::any())->method('getScheduledEntityUpdates')->willReturn([new LocalizedFallbackValue()]);
        $uow->expects(static::any())->method('getScheduledEntityInsertions')->willReturn([]);
        $uow->expects(static::any())->method('getScheduledEntityDeletions')->willReturn([]);

        // assertions
        $this->messageFactory->expects(static::never())->method('createMassMessage');
        $this->messageProducer->expects(static::never())->method('send');

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushChangedSlugWithoutChangedPrototypesUp()
    {
        /** @var OnFlushEventArgs|MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $this->configManager->expects(static::any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        /** @var UnitOfWork|MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(static::any())->method('getUnitOfWork')->willReturn($uow);
        $event->expects(static::any())->method('getEntityManager')->willReturn($em);

        /** @var SluggableInterface|MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);

        $slugPrototypes = $this->createMock(Collection::class);
        $entity->expects(static::once())->method('getSlugPrototypes')->willReturn($slugPrototypes);

        $slugPrototypes->expects(static::once())->method('count')->willReturn(0);

        $uow->expects(static::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entity, new LocalizedFallbackValue()]);
        $uow->expects(static::any())->method('getScheduledEntityInsertions')->willReturn([]);
        $uow->expects(static::any())->method('getScheduledEntityDeletions')->willReturn([]);

        // assertions
        $this->messageFactory->expects(static::never())->method('createMassMessage');
        $this->messageProducer->expects(static::never())->method('send');

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushChangedSlugWithChangedPrototypesIns()
    {
        /** @var OnFlushEventArgs|MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $entityId = 1;

        $entity = $this->prepareSluggableEntity($event);
        $entity->expects(static::once())->method('getId')->willReturn($entityId);

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        // assertions
        $message = ['id' => [$entityId], 'class' => \get_class($entity), 'createRedirect' => true];

        $this->messageFactory->expects(static::once())
            ->method('createMassMessage')
            ->with(\get_class($entity), [$entityId], true)
            ->willReturn($message);

        $this->messageProducer->expects(static::once())
            ->method('send')
            ->with(Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $message);

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushChangedSlugWithChangedPrototypesDel()
    {
        /** @var SluggableInterface|MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $entityId = 1;

        /** @var OnFlushEventArgs|MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $uow = $this->prepareUow($event, $entity);

        $prototype = new LocalizedFallbackValue();

        $slugPrototypes = $this->createMock(Collection::class);
        $entity->expects(static::once())->method('getSlugPrototypes')->willReturn($slugPrototypes);
        $slugPrototypes->expects(static::once())->method('count')->willReturn(1);

        $entity->expects(static::once())
            ->method('hasSlugPrototype')
            ->with($prototype)
            ->willReturn(true);

        $entity->expects(static::once())->method('getId')->willReturn($entityId);

        $uow->expects(static::any())->method('getScheduledEntityInsertions')->willReturn([]);
        $uow->expects(static::any())->method('getScheduledEntityDeletions')->willReturn([$prototype]);

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        // assertions
        $message = ['id' => [$entityId], 'class' => \get_class($entity), 'createRedirect' => true];

        $this->messageFactory->expects(static::once())
            ->method('createMassMessage')
            ->with(\get_class($entity), [$entityId], true)
            ->willReturn($message);

        $this->messageProducer->expects(static::once())
            ->method('send')
            ->with(Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $message);

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushWithDisabledListener()
    {
        $event = $this->createMock(OnFlushEventArgs::class);
        $event->expects(static::never())->method('getEntityManager');
        $this->configManager->expects(static::never())->method('get');
        $this->messageFactory->expects(static::never())->method('createMessage');

        $this->assertAndDisableListener();

        // assertions
        $this->messageFactory->expects(static::never())->method('createMassMessage');
        $this->messageProducer->expects(static::never())->method('send');

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
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

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        // assertions
        $message = [
            'id' => [$entityId],
            'class' => get_class($entity),
            'createRedirect' => $createRedirect
        ];

        $this->messageFactory->expects(static::once())
            ->method('createMassMessage')
            ->with(get_class($entity), [$entityId], $expectedCreateRedirect)
            ->willReturn($message);

        $this->messageProducer->expects(static::once())
            ->method('send')
            ->with(Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $message);

        $this->sluggableEntityListener->postPersist(
            new LifecycleEventArgs($entity, $this->createMock(ObjectManager::class))
        );
        $this->sluggableEntityListener->postFlush();
    }

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

        $this->configManager->expects(static::any())
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

        $this->messageFactory->expects(static::exactly(2))
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

        $this->messageProducer->expects(static::exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $messageWithoutRedirect],
                [Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $messageWithRedirect]
            );

        $this->sluggableEntityListener->postFlush();
    }

    /**
     * @param OnFlushEventArgs|MockObject $event
     * @return SluggableInterface|MockObject
     */
    protected function prepareSluggableEntity($event)
    {
        /** @var SluggableInterface|MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);

        $uow  = $this->prepareUow($event, $entity);

        $prototype = new LocalizedFallbackValue();

        $slugPrototypes = $this->createMock(Collection::class);

        $entity
            ->expects(static::once())
            ->method('getSlugPrototypes')
            ->willReturn($slugPrototypes);

        $slugPrototypes
            ->expects(static::once())
            ->method('count')
            ->willReturn(1);

        $entity->expects(static::once())
            ->method('hasSlugPrototype')
            ->with($prototype)
            ->willReturn(true);

        $uow->expects(static::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$prototype]);
        $uow->expects(static::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        return $entity;
    }

    /**
     * @param OnFlushEventArgs|MockObject $event
     * @param SluggableInterface|MockObject $entity
     * @return UnitOfWork|MockObject
     */
    protected function prepareUow($event, $entity)
    {
        /** @var UnitOfWork|MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects(static::any())->method('getUnitOfWork')->willReturn($uow);
        $event->expects(static::any())->method('getEntityManager')->willReturn($em);
        $uow->expects(static::any())->method('getScheduledEntityUpdates')->willReturn([$entity]);

        return $uow;
    }

    protected function assertAndDisableListener()
    {
        static::assertInstanceOf(OptionalListenerInterface::class, $this->sluggableEntityListener);
        $this->sluggableEntityListener->setEnabled(false);
    }
}
