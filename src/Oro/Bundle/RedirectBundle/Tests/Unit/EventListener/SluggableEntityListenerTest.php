<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\EventListener\SluggableEntityListener;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SluggableEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageFactory;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SluggableEntityListener */
    private $sluggableEntityListener;

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

    public function testPostPersistDisabledDirectUrl(): void
    {
        $args = $this->createMock(LifecycleEventArgs::class);

        $entity = $this->createMock(SluggableInterface::class);
        $args->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        // assertions
        $this->messageFactory->expects(self::never())
            ->method('createMassMessage');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testPostPersistNotSluggableEntity(): void
    {
        $args = $this->createMock(LifecycleEventArgs::class);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $entity = new \stdClass();
        $args->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);

        // assertions
        $this->messageFactory->expects(self::never())
            ->method('createMassMessage');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testPostPersistWithDisabledListener(): void
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $args->expects(self::never())
            ->method('getEntity');
        $this->configManager->expects(self::never())
            ->method('get');
        $this->messageFactory->expects(self::never())
            ->method('createMessage');

        $this->assertAndDisableListener();

        // assertions
        $this->messageFactory->expects(self::never())
            ->method('createMassMessage');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testPostPersist(): void
    {
        $args = $this->createMock(LifecycleEventArgs::class);

        $entityId = 1;

        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects(self::once())
            ->method('getId')
            ->willReturn($entityId);
        $args->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        // assertions
        $message = ['id' => [$entityId], 'class' => \get_class($entity), 'createRedirect' => true];

        $this->messageFactory->expects(self::once())
            ->method('createMassMessage')
            ->with(\get_class($entity), [$entityId], true)
            ->willReturn($message);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(GenerateDirectUrlForEntitiesTopic::getName(), $message);

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testPostPersistWithDraft(): void
    {
        $args = $this->createMock(LifecycleEventArgs::class);

        $entity = $this->getEntity(Page::class, ['id' => 1, 'draftUuid' => 42]);
        $args->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);

        // assertions
        $this->configManager->expects(self::never())
            ->method('get');
        $this->messageFactory->expects(self::never())
            ->method('createMassMessage');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->sluggableEntityListener->postPersist($args);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushDisabledDirectUrl(): void
    {
        $event = $this->createMock(OnFlushEventArgs::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->prepareSluggableEntity($event);

        // assertions
        $this->messageFactory->expects(self::never())
            ->method('createMassMessage');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushNoChangedSlugs(): void
    {
        $event = $this->createMock(OnFlushEventArgs::class);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $uow = $this->createMock(UnitOfWork::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects(self::any())
            ->method('getEntityManager')
            ->willReturn($em);
        $uow->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new LocalizedFallbackValue()]);
        $uow->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new LocalizedFallbackValue()]);
        $uow->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        // assertions
        $this->messageFactory->expects(self::never())
            ->method('createMassMessage');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushChangedSlugWithoutChangedPrototypesUp(): void
    {
        $event = $this->createMock(OnFlushEventArgs::class);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $uow = $this->createMock(UnitOfWork::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects(self::any())
            ->method('getEntityManager')
            ->willReturn($em);

        $entity = $this->createMock(SluggableInterface::class);

        $slugPrototypes = $this->createMock(Collection::class);
        $entity->expects(self::once())
            ->method('getSlugPrototypes')
            ->willReturn($slugPrototypes);

        $slugPrototypes->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $uow->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entity, new LocalizedFallbackValue()]);
        $uow->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        // assertions
        $this->messageFactory->expects(self::never())
            ->method('createMassMessage');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushChangedSlugWithChangedPrototypesIns(): void
    {
        $event = $this->createMock(OnFlushEventArgs::class);

        $entityId = 1;

        $entity = $this->prepareSluggableEntity($event);
        $entity->expects(self::once())
            ->method('getId')
            ->willReturn($entityId);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        // assertions
        $message = ['id' => [$entityId], 'class' => \get_class($entity), 'createRedirect' => true];

        $this->messageFactory->expects(self::once())
            ->method('createMassMessage')
            ->with(\get_class($entity), [$entityId], true)
            ->willReturn($message);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(GenerateDirectUrlForEntitiesTopic::getName(), $message);

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushChangedSlugWithChangedPrototypesDel(): void
    {
        $entity = $this->createMock(SluggableInterface::class);
        $entityId = 1;

        $event = $this->createMock(OnFlushEventArgs::class);

        $uow = $this->prepareUow($event, $entity);

        $prototype = new LocalizedFallbackValue();

        $slugPrototypes = $this->createMock(Collection::class);
        $entity->expects(self::once())
            ->method('getSlugPrototypes')
            ->willReturn($slugPrototypes);
        $slugPrototypes->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $entity->expects(self::once())
            ->method('hasSlugPrototype')
            ->with($prototype)
            ->willReturn(true);

        $entity->expects(self::once())
            ->method('getId')
            ->willReturn($entityId);

        $uow->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$prototype]);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        // assertions
        $message = ['id' => [$entityId], 'class' => \get_class($entity), 'createRedirect' => true];

        $this->messageFactory->expects(self::once())
            ->method('createMassMessage')
            ->with(\get_class($entity), [$entityId], true)
            ->willReturn($message);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(GenerateDirectUrlForEntitiesTopic::getName(), $message);

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    public function testOnFlushWithDisabledListener(): void
    {
        $event = $this->createMock(OnFlushEventArgs::class);
        $event->expects(self::never())
            ->method('getEntityManager');
        $this->configManager->expects(self::never())
            ->method('get');
        $this->messageFactory->expects(self::never())
            ->method('createMessage');

        $this->assertAndDisableListener();

        // assertions
        $this->messageFactory->expects(self::never())
            ->method('createMassMessage');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->sluggableEntityListener->onFlush($event);
        $this->sluggableEntityListener->postFlush();
    }

    /**
     * @dataProvider slugPrototypeWithRedirectDataProvider
     */
    public function testPostFlushWithSlugPrototypeWithRedirect(
        bool $createRedirect,
        bool $expectedCreateRedirect
    ): void {
        $entityId = 1;
        $entity = new SluggableEntityStub();
        $entity->setId($entityId);
        $entity->setSlugPrototypesWithRedirect(new SlugPrototypesWithRedirect(
            new ArrayCollection([new LocalizedFallbackValue()]),
            $createRedirect
        ));

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        // assertions
        $message = [
            'id' => [$entityId],
            'class' => get_class($entity),
            'createRedirect' => $createRedirect
        ];

        $this->messageFactory->expects(self::once())
            ->method('createMassMessage')
            ->with(get_class($entity), [$entityId], $expectedCreateRedirect)
            ->willReturn($message);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(GenerateDirectUrlForEntitiesTopic::getName(), $message);

        $this->sluggableEntityListener->postPersist(
            new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class))
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

    public function testPostFlushWithSlugPrototypeWithRedirectWithMultiple(): void
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

        $this->configManager->expects(self::any())
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

        $this->messageFactory->expects(self::exactly(2))
            ->method('createMassMessage')
            ->withConsecutive(
                [get_class($entityWithoutRedirect), [$entityWithoutRedirectId]],
                [get_class($entityWithoutRedirect), [$entityWithRedirectId]]
            )
            ->willReturnOnConsecutiveCalls($messageWithoutRedirect, $messageWithRedirect);

        $this->sluggableEntityListener->postPersist(new LifecycleEventArgs(
            $entityWithoutRedirect,
            $this->createMock(EntityManagerInterface::class)
        ));

        $this->sluggableEntityListener->postPersist(new LifecycleEventArgs(
            $entityWithRedirect,
            $this->createMock(EntityManagerInterface::class)
        ));

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [GenerateDirectUrlForEntitiesTopic::getName(), $messageWithoutRedirect],
                [GenerateDirectUrlForEntitiesTopic::getName(), $messageWithRedirect]
            );

        $this->sluggableEntityListener->postFlush();
    }

    private function prepareSluggableEntity(
        OnFlushEventArgs|\PHPUnit\Framework\MockObject\MockObject $event
    ): SluggableInterface|\PHPUnit\Framework\MockObject\MockObject {
        $entity = $this->createMock(SluggableInterface::class);

        $uow  = $this->prepareUow($event, $entity);

        $prototype = new LocalizedFallbackValue();

        $slugPrototypes = $this->createMock(Collection::class);

        $entity->expects(self::once())
            ->method('getSlugPrototypes')
            ->willReturn($slugPrototypes);

        $slugPrototypes->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $entity->expects(self::once())
            ->method('hasSlugPrototype')
            ->with($prototype)
            ->willReturn(true);

        $uow->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$prototype]);
        $uow->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        return $entity;
    }

    private function prepareUow(
        OnFlushEventArgs|\PHPUnit\Framework\MockObject\MockObject $event,
        SluggableInterface $entity
    ): UnitOfWork|\PHPUnit\Framework\MockObject\MockObject {
        $uow = $this->createMock(UnitOfWork::class);

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects(self::any())
            ->method('getEntityManager')
            ->willReturn($em);
        $uow->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entity]);

        return $uow;
    }

    private function assertAndDisableListener(): void
    {
        self::assertInstanceOf(OptionalListenerInterface::class, $this->sluggableEntityListener);
        $this->sluggableEntityListener->setEnabled(false);
    }
}
