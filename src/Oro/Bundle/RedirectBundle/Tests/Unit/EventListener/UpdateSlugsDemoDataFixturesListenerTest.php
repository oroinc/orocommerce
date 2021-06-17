<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\DemoDataFixturesListenerTestCase;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\EventListener\UpdateSlugsDemoDataFixturesListener;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

class UpdateSlugsDemoDataFixturesListenerTest extends DemoDataFixturesListenerTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SlugEntityGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $generator;

    /** @var UrlStorageCache|\PHPUnit\Framework\MockObject\MockObject */
    private $urlStorageCache;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $redirectRepository;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $sluggableRepository;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $slugRepository;

    /** @var ClassMetadataFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataFactory;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->generator = $this->createMock(SlugEntityGenerator::class);
        $this->urlStorageCache = $this->createMock(UrlStorageCache::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->redirectRepository = $this->createMock(EntityRepository::class);
        $this->sluggableRepository = $this->createMock(EntityRepository::class);
        $this->slugRepository = $this->createMock(EntityRepository::class);
        $this->metadataFactory = $this->createMock(ClassMetadataFactory::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getListener()
    {
        return new UpdateSlugsDemoDataFixturesListener(
            $this->listenerManager,
            $this->configManager,
            $this->generator,
            $this->urlStorageCache
        );
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

        /* @var Slug $slug1 */
        $slug1 = $this->getEntity(
            Slug::class,
            [
                'id' => 1,
                'scopes' => [
                    $this->getEntity(Scope::class, ['id' => 1]),
                ],
            ]
        );

        $redirect1 = $this->createMock(Redirect::class);
        $redirect2 = $this->createMock(Redirect::class);

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

        $this->event->expects($this->any())
            ->method('getObjectManager')
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

        $this->generator->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                [$sluggable1, true],
                [$sluggable2, false]
            );

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
            ->with(self::LISTENERS);

        $this->listener->onPostLoad($this->event);
    }

    public function testOnPostLoadWithDisabledDirectUrls()
    {
        /* @var Slug $slug1 */
        $slug1 = $this->getEntity(
            Slug::class,
            [
                'id' => 1,
                'scopes' => [
                    $this->getEntity(Scope::class, ['id' => 1]),
                ],
            ]
        );

        $redirect1 = $this->createMock(Redirect::class);

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

        $this->event->expects($this->any())
            ->method('getObjectManager')
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
            ->with(self::LISTENERS);

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

        $this->listenerManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onPostLoad($this->event);
    }

    private function getMetadata(string $className): ClassMetadata
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn($className);

        return $metadata;
    }
}
