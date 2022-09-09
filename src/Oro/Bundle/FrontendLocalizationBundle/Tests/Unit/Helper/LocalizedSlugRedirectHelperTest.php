<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Helper;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\FrontendLocalizationBundle\Helper\LocalizedSlugRedirectHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;
use Oro\Bundle\RedirectBundle\Routing\Router;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class LocalizedSlugRedirectHelperTest extends TestCase
{
    use EntityTrait;

    private SlugSourceEntityProviderInterface|MockObject $slugSourceEntityProvider;

    private ManagerRegistry|MockObject $registry;

    private CanonicalUrlGenerator|MockObject $canonicalUrlGenerator;

    private WebsiteManager|MockObject $websiteManager;

    private Router|MockObject $router;

    private LocalizedSlugRedirectHelper $helper;

    protected function setUp(): void
    {
        $this->slugSourceEntityProvider = $this->createMock(SlugSourceEntityProviderInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->router = $this->createMock(Router::class);

        $this->helper = new LocalizedSlugRedirectHelper(
            $this->slugSourceEntityProvider,
            $this->registry,
            $this->canonicalUrlGenerator,
            $this->websiteManager,
            $this->router
        );
    }

    public function testGetRouteByLocalizationWhenNoUsedSlug(): void
    {
        $localization = new Localization();
        $urlString = 'http://example.com/';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn([]);
        $this->slugSourceEntityProvider->expects($this->never())
            ->method('getSourceEntityBySlug');
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->assertEquals($urlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    public function testGetRouteByLocalizationWhenSlugAlreadyInRightLocalization(): void
    {
        $localization = new Localization();
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $usedSlug->setLocalization($localization);

        $urlString = 'http://example.com/slug1';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn(['_used_slug' => $usedSlug]);
        $this->refreshSlug($usedSlug);
        $this->slugSourceEntityProvider->expects($this->never())
            ->method('getSourceEntityBySlug');
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->assertEquals($urlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    public function testGetRouteByLocalizationWhenSlugAlreadyInRightLocalizationWithContextLocalizedNotFound(): void
    {
        $localization = new Localization();
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $usedSlug->setLocalization($localization);
        $contextUsedSlug = new Slug();
        $contextUsedSlug->setUrl('/context1');
        $contextUsedSlug->setLocalization($localization);
        $attribute = [
            '_used_slug' => $usedSlug,
            '_context_url_attributes' => [
                [
                    '_used_slug' => $contextUsedSlug
                ]
            ]
        ];

        $urlString = 'http://example.com/slug1';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn($attribute);
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

        $this->assertEquals($urlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    public function testGetRouteByLocalizationWhenNoSourceEntity(): void
    {
        $localization = new Localization();
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $attribute = ['_used_slug' => $usedSlug];

        $urlString = 'http://example.com/slug1';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn($attribute);
        $this->slugSourceEntityProvider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($usedSlug)
            ->willReturn(null);
        $this->refreshSlug($usedSlug);
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->assertEquals($urlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    public function testGetRouteByLocalizationWhenNoLocalizedSlug(): void
    {
        $localization = new Localization();
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $sourceEntity = new Page();
        $attribute = ['_used_slug' => $usedSlug];

        $urlString = 'http://example.com/slug1';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn($attribute);
        $this->slugSourceEntityProvider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($usedSlug)
            ->willReturn($sourceEntity);
        $this->refreshSlug($usedSlug);
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->assertEquals($urlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    public function testGetRouteByLocalizationWhenSameUrl(): void
    {
        $localization = new Localization();
        $usedSlug = new Slug();
        $usedSlug->setUrl('/slug1');
        $sourceEntity = new Page();
        $sourceEntity->addSlug($usedSlug);
        $attribute = ['_used_slug' => $usedSlug];

        $urlString = 'http://example.com/slug1';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn($attribute);
        $this->slugSourceEntityProvider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($usedSlug)
            ->willReturn($sourceEntity);
        $this->refreshSlug($usedSlug);
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->assertEquals($urlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    public function testGetRouteByLocalization(): void
    {
        $localization = new Localization();
        $usedSlug = $this->getEntity(Slug::class, ['id' => 333, 'url' => '/old-url']);
        $localizedSlug = $this->getEntity(
            Slug::class,
            ['id' => 777, 'url' => '/new-url', 'localization' => $localization]
        );
        $sourceEntity = new Page();
        $sourceEntity->addSlug($localizedSlug);
        $attribute = ['_used_slug' => $usedSlug];

        $urlString = 'http://example.com/old-url';
        $newUrlString = 'http://example.com/new-url';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn($attribute);
        $this->slugSourceEntityProvider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($usedSlug)
            ->willReturn($sourceEntity);
        $this->refreshSlug($usedSlug);

        $website = new Website();
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with('/new-url', $website)
            ->willReturn($newUrlString);
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->assertEquals($newUrlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    public function testGetRouteByLocalizationWithContext(): void
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
        $attribute = [
            '_used_slug' => $usedSlug,
            '_context_url_attributes' => [
                [
                    '_used_slug' => $contextUsedSlug
                ]
            ]
        ];

        $urlString = 'http://example.com/context-old/_item/old-url';
        $newUrlString = 'http://example.com/context-new/_item/new-url';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn($attribute);
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
            ->willReturn($newUrlString);
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->assertEquals($newUrlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    public function testGetRouteByLocalizationWithOnlyContextChange(): void
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
        $attribute = [
            '_used_slug' => $usedSlug,
            '_context_url_attributes' => [
                [
                    '_used_slug' => $contextUsedSlug
                ]
            ]
        ];

        $urlString = 'http://example.com/context-old/_item/old-url';
        $newUrlString = 'http://website.loc/context-new/_item/old-url';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn($attribute);
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
            ->willReturn($newUrlString);
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->assertEquals($newUrlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    public function testGetRouteByLocalizationWithContextOnlySlugChanged(): void
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

        $attribute = [
            '_used_slug' => $usedSlug,
            '_context_url_attributes' => [
                [
                    '_used_slug' => $contextUsedSlug
                ]
            ]
        ];

        $urlString = 'http://example.com/context-old/_item/old-url';
        $newUrlString = 'http://example.com/context-old/_item/new-url';
        $this->router->expects($this->once())
            ->method('match')
            ->with(parse_url($urlString, PHP_URL_PATH))
            ->willReturn($attribute);
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
            ->willReturn($newUrlString);
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->assertEquals($newUrlString, $this->helper->getLocalizedUrl($urlString, $localization));
    }

    private function refreshSlug(Slug $usedSlug): void
    {
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);
        $manager->expects($this->once())
            ->method('refresh')
            ->with($usedSlug);
    }
}
