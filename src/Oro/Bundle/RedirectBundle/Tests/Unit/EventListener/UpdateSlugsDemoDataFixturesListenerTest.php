<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\EventListener\UpdateSlugsDemoDataFixturesListener;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UpdateSlugsDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var OptionalListenerManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $listenerManager;

    /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var SlugEntityGenerator|\PHPUnit_Framework_MockObject_MockObject */
    protected $generator;

    /** @var UrlStorageCache|\PHPUnit_Framework_MockObject_MockObject */
    protected $urlStorageCache;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $redirectRepository;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $sluggableRepository;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $slugRepository;

    /** @var ClassMetadataFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $metadataFactory;

    /** @var MigrationDataFixturesEvent|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var UpdateSlugsDemoDataFixturesListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->doctrine = $this->createMock(RegistryInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->generator = $this->createMock(SlugEntityGenerator::class);
        $this->urlStorageCache = $this->createMock(UrlStorageCache::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->redirectRepository = $this->createMock(EntityRepository::class);
        $this->sluggableRepository = $this->createMock(EntityRepository::class);
        $this->slugRepository = $this->createMock(EntityRepository::class);
        $this->metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $this->event = $this->createMock(MigrationDataFixturesEvent::class);

        $this->listener = new UpdateSlugsDemoDataFixturesListener(
            $this->listenerManager,
            $this->doctrine,
            $this->configManager,
            $this->generator,
            $this->urlStorageCache
        );
    }

    public function testOnPreLoad()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('disableListeners')
            ->with(UpdateSlugsDemoDataFixturesListener::LISTENERS);

        $this->listener->onPreLoad($this->event);
    }

    public function testOnPreLoadWithNoDemoFixtures()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('disableListeners');

        $this->listener->onPreLoad($this->event);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnPostLoad()
    {
        $sluggable1 = $this->getEntity(SluggableEntityStub::class, ['id' => 1]);
        $sluggable2 = $this->getEntity(
            SluggableEntityStub::class,
            [
                'id' => 2,
                'slugPrototypesWithRedirect' => new SlugPrototypesWithRedirect(new ArrayCollection(), false)
            ]
        );

        /* @var $slug1 Slug */
        $slug1 = $this->getEntity(
            Slug::class,
            [
                'id' => 1,
                'scopes' => [
                    $this->getEntity(Scope::class, ['id' => 1]),
                ],
            ]
        );

        $redirect1 = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setScopes'])
            ->getMock();

        $redirect2 = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setScopes'])
            ->getMock();

        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->event->expects($this->once())
            ->method('log')
            ->with('updating slugs');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $this->doctrine->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([
                $this->getMetadata(Item::class),
                $this->getMetadata(SluggableEntityStub::class),
            ]);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [SluggableEntityStub::class, $this->sluggableRepository],
                [Slug::class, $this->slugRepository],
                [Redirect::class, $this->redirectRepository],
            ]);

        $this->sluggableRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$sluggable1, $sluggable2]);

        $this->generator->expects($this->at(0))
            ->method('generate')
            ->with($sluggable1, true);

        $this->generator->expects($this->at(1))
            ->method('generate')
            ->with($sluggable2, false);

        $this->urlStorageCache->expects($this->once())
            ->method('flushAll');

        $this->slugRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$slug1]);

        $this->redirectRepository->expects($this->once())
            ->method('findBy')
            ->with(['slug' => $slug1])
            ->willReturn([$redirect1, $redirect2]);

        $redirect1->expects($this->once())
            ->method('setScopes')
            ->with($slug1->getScopes());

        $redirect2->expects($this->once())
            ->method('setScopes')
            ->with($slug1->getScopes());

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(UpdateSlugsDemoDataFixturesListener::LISTENERS);

        $this->listener->onPostLoad($this->event);
    }

    public function testOnPostLoadWithDisabledDirectUrls()
    {
        /* @var $slug1 Slug */
        $slug1 = $this->getEntity(
            Slug::class,
            [
                'id' => 1,
                'scopes' => [
                    $this->getEntity(Scope::class, ['id' => 1]),
                ],
            ]
        );

        $redirect1 = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setScopes'])
            ->getMock();

        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->event->expects($this->once())
            ->method('log')
            ->with('updating slugs');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->doctrine->expects($this->never())
            ->method('getEntityManager');

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects($this->never())
            ->method('getMetadataFactory');

        $this->metadataFactory->expects($this->never())
            ->method($this->anything());

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Slug::class, $this->slugRepository],
                [Redirect::class, $this->redirectRepository],
            ]);

        $this->sluggableRepository->expects($this->never())
            ->method($this->anything());

        $this->generator->expects($this->never())
            ->method($this->anything());

        $this->urlStorageCache->expects($this->never())
            ->method($this->anything());

        $this->slugRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$slug1]);

        $this->redirectRepository->expects($this->once())
            ->method('findBy')
            ->with(['slug' => $slug1])
            ->willReturn([$redirect1]);

        $redirect1->expects($this->once())
            ->method('setScopes')
            ->with($slug1->getScopes());

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(UpdateSlugsDemoDataFixturesListener::LISTENERS);

        $this->listener->onPostLoad($this->event);
    }

    public function testOnPostLoadWithNoDemoFixtures()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->event->expects($this->never())
            ->method('log');

        $this->configManager->expects($this->never())
            ->method($this->anything());

        $this->doctrine->expects($this->never())
            ->method($this->anything());

        $this->listenerManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onPostLoad($this->event);
    }

    /**
     * @param string $className
     * @return ClassMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMetadata($className)
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn($className);

        return $metadata;
    }
}
