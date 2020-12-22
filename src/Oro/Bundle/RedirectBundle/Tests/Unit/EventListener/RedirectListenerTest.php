<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\EventListener\RedirectListener;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class RedirectListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userLocalizationManager;

    /**
     * @var SlugSourceEntityProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $slugSourceEntityProvider;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteManager;

    /**
     * @var RedirectListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->userLocalizationManager = $this->createMock(UserLocalizationManager::class);
        $this->slugSourceEntityProvider = $this->createMock(SlugSourceEntityProviderInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->listener = new RedirectListener(
            $this->userLocalizationManager,
            $this->slugSourceEntityProvider,
            $this->registry,
            $this->canonicalUrlGenerator,
            $this->websiteManager
        );
    }

    public function testOnRequestWhenNoUsedSlug()
    {
        $this->userLocalizationManager->expects($this->never())
            ->method('getCurrentLocalization');
        $this->slugSourceEntityProvider->expects($this->never())
            ->method('getSourceEntityBySlug');
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST);
        $this->listener->onRequest($event);
        $this->assertNull($event->getResponse());
    }

    public function testOnRequestWhenNoLocalization()
    {
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);
        $this->slugSourceEntityProvider->expects($this->never())
            ->method('getSourceEntityBySlug');
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request([], [], ['_used_slug' => new Slug()]),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNull($event->getResponse());
    }

    public function testOnRequestWhenSlugAlreadyInRightLocalization()
    {
        $localization = new Localization();
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $usedSlug->setLocalization($localization);
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);
        $manager->expects($this->once())
            ->method('refresh')
            ->with($usedSlug);
        $this->slugSourceEntityProvider->expects($this->never())
            ->method('getSourceEntityBySlug');
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request([], [], ['_used_slug' => $usedSlug]),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNull($event->getResponse());
    }

    public function testOnRequestWhenSlugAlreadyInRightLocalizationWithContextLocalizedNotFound()
    {
        $localization = new Localization();
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $usedSlug->setLocalization($localization);

        $contextUsedSlug = new Slug();
        $contextUsedSlug->setUrl('/context1');
        $contextUsedSlug->setLocalization($localization);

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);

        $this->slugSourceEntityProvider->expects($this->never())
            ->method('getSourceEntityBySlug');

        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request(
                [],
                [],
                [
                    '_used_slug' => $usedSlug,
                    '_context_url_attributes' => [
                        [
                            '_used_slug' => $contextUsedSlug
                        ]
                    ]
                ]
            ),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNull($event->getResponse());
    }

    public function testOnRequestWhenNoSourceEntity()
    {
        $localization = new Localization();
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->slugSourceEntityProvider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($usedSlug)
            ->willReturn(null);
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);
        $manager->expects($this->once())
            ->method('refresh')
            ->with($usedSlug);
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request([], [], ['_used_slug' => $usedSlug]),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNull($event->getResponse());
    }

    public function testOnRequestWhenNoLocalizedSlug()
    {
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $sourceEntity = new Page();
        $localization = new Localization();
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->slugSourceEntityProvider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($usedSlug)
            ->willReturn($sourceEntity);
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);
        $manager->expects($this->once())
            ->method('refresh')
            ->with($usedSlug);
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request([], [], ['_used_slug' => $usedSlug]),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNull($event->getResponse());
    }

    public function testOnRequestWhenSameUrl()
    {
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $sourceEntity = new Page();
        $sourceEntity->addSlug($usedSlug);
        $localization = new Localization();
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->slugSourceEntityProvider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($usedSlug)
            ->willReturn($sourceEntity);
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);
        $manager->expects($this->once())
            ->method('refresh')
            ->with($usedSlug);
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request([], [], ['_used_slug' => $usedSlug]),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNull($event->getResponse());
    }

    public function testOnRequest()
    {
        $localization = new Localization();
        $usedSlug = $this->getEntity(Slug::class, ['id' => 333, 'url' => '/old-url']);
        $localizedSlug = $this->getEntity(
            Slug::class,
            ['id' => 777, 'url' => '/new-url', 'localization' => $localization]
        );
        $sourceEntity = new Page();
        $sourceEntity->addSlug($localizedSlug);
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->slugSourceEntityProvider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($usedSlug)
            ->willReturn($sourceEntity);
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);
        $manager->expects($this->once())
            ->method('refresh')
            ->with($usedSlug);

        $website = new Website();
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with('/new-url', $website)
            ->willReturn('http://website.loc/new-url');
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request([], [], ['_used_slug' => $usedSlug]),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNotNull($event->getResponse());
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertEquals('http://website.loc/new-url', $event->getResponse()->getTargetUrl());
    }

    public function testOnRequestWithContext()
    {
        $localization = new Localization();
        $usedSlug = $this->getEntity(Slug::class, ['id' => 333, 'url' => '/old-url']);
        $localizedSlug = $this->getEntity(
            Slug::class,
            ['id' => 777, 'url' => '/new-url', 'localization' => $localization]
        );
        $contextUsedSlug = $this->getEntity(Slug::class, ['id' => 33, 'url' => '/context-old']);
        $localizedContextSlug = $this->getEntity(
            Slug::class,
            ['id' => 77, 'url' => '/context-new', 'localization' => $localization]
        );
        $sourceEntity = new Page();
        $sourceEntity->addSlug($usedSlug);
        $sourceEntity->addSlug($localizedSlug);
        $sourceEntityContext = new Category();
        $sourceEntityContext->addSlug($contextUsedSlug);
        $sourceEntityContext->addSlug($localizedContextSlug);

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->slugSourceEntityProvider->expects($this->exactly(2))
            ->method('getSourceEntityBySlug')
            ->willReturnMap([
                [$usedSlug, $sourceEntity],
                [$contextUsedSlug, $sourceEntityContext]
            ]);

        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);

        $website = new Website();
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with('/context-new/_item/new-url', $website)
            ->willReturn('http://website.loc/context-new/_item/new-url');
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request(
                [],
                [],
                [
                    '_used_slug' => $usedSlug,
                    '_context_url_attributes' => [
                        [
                            '_used_slug' => $contextUsedSlug
                        ]
                    ]
                ]
            ),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNotNull($event->getResponse());
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertEquals('http://website.loc/context-new/_item/new-url', $event->getResponse()->getTargetUrl());
    }

    public function testOnRequestWithOnlyContextChange()
    {
        $localization = new Localization();
        $usedSlug = $this->getEntity(Slug::class, ['id' => 333, 'url' => '/old-url']);
        $contextUsedSlug = $this->getEntity(Slug::class, ['id' => 33, 'url' => '/context-old']);
        $localizedContextSlug = $this->getEntity(
            Slug::class,
            ['id' => 77, 'url' => '/context-new', 'localization' => $localization]
        );
        $sourceEntity = new Page();
        $sourceEntity->addSlug($usedSlug);
        $sourceEntityContext = new Category();
        $sourceEntityContext->addSlug($contextUsedSlug);
        $sourceEntityContext->addSlug($localizedContextSlug);

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->slugSourceEntityProvider->expects($this->exactly(2))
            ->method('getSourceEntityBySlug')
            ->willReturnMap([
                [$usedSlug, $sourceEntity],
                [$contextUsedSlug, $sourceEntityContext]
            ]);

        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);

        $website = new Website();
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with('/context-new/_item/old-url', $website)
            ->willReturn('http://website.loc/context-new/_item/old-url');
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request(
                [],
                [],
                [
                    '_used_slug' => $usedSlug,
                    '_context_url_attributes' => [
                        [
                            '_used_slug' => $contextUsedSlug
                        ]
                    ]
                ]
            ),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNotNull($event->getResponse());
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertEquals('http://website.loc/context-new/_item/old-url', $event->getResponse()->getTargetUrl());
    }

    public function testOnRequestWithContextOnlySlugChanged()
    {
        $localization = new Localization();
        $usedSlug = $this->getEntity(Slug::class, ['id' => 333, 'url' => '/old-url']);
        $localizedSlug = $this->getEntity(
            Slug::class,
            ['id' => 777, 'url' => '/new-url', 'localization' => $localization]
        );
        $contextUsedSlug = $this->getEntity(Slug::class, ['id' => 33, 'url' => '/context-old']);
        $sourceEntity = new Page();
        $sourceEntity->addSlug($usedSlug);
        $sourceEntity->addSlug($localizedSlug);
        $sourceEntityContext = new Category();
        $sourceEntityContext->addSlug($contextUsedSlug);

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->slugSourceEntityProvider->expects($this->exactly(2))
            ->method('getSourceEntityBySlug')
            ->willReturnMap([
                [$usedSlug, $sourceEntity],
                [$contextUsedSlug, $sourceEntityContext]
            ]);

        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);

        $website = new Website();
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with('/context-old/_item/new-url', $website)
            ->willReturn('http://website.loc/context-old/_item/new-url');
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            new Request(
                [],
                [],
                [
                    '_used_slug' => $usedSlug,
                    '_context_url_attributes' => [
                        [
                            '_used_slug' => $contextUsedSlug
                        ]
                    ]
                ]
            ),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onRequest($event);
        $this->assertNotNull($event->getResponse());
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertEquals('http://website.loc/context-old/_item/new-url', $event->getResponse()->getTargetUrl());
    }
}
