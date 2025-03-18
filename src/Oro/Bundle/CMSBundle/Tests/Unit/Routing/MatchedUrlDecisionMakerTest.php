<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Routing;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SlugEntityFinder;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MatchedUrlDecisionMakerTest extends TestCase
{
    private FrontendHelper|MockObject $frontendHelper;

    private ThemeConfigurationProvider|MockObject $themeConfigurationProvider;

    private ThemeManager|MockObject $themeManager;

    private SlugEntityFinder|MockObject $slugEntityFinder;

    private ConfigManager|MockObject $configManager;

    private ApplicationState|MockObject $applicationState;

    #[\Override]
    protected function setUp(): void
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->slugEntityFinder = $this->createMock(SlugEntityFinder::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->applicationState = $this->createMock(ApplicationState::class);
    }

    /**
     * @dataProvider getMatchesDataProvider
     */
    public function testMatches(bool $isFrontend, array $skippedUrlPatterns, string $url, bool $expected): void
    {
        $maker = $this->createMatchedUrlDecisionMaker($skippedUrlPatterns);

        $this->frontendHelper->expects(self::any())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn($isFrontend);

        self::assertEquals($expected, $maker->matches($url));
    }

    public function getMatchesDataProvider(): array
    {
        return [
            'allowed url' => [
                true,
                [],
                '/test',
                true
            ],
            'not frontend' => [
                false,
                [],
                '/test',
                false
            ],
            'skipped frontend' => [
                true,
                ['/api/'],
                '/api/test',
                false
            ],
        ];
    }


    public function testShouldResetInternalCacheWhenNewPatternIsAdded(): void
    {
        $this->frontendHelper->expects(self::any())
            ->method('isFrontendUrl')
            ->willReturn(true);

        $maker = $this->createMatchedUrlDecisionMaker(['/folder1/']);

        self::assertFalse($maker->matches('/folder1/file.html'));
        self::assertTrue($maker->matches('/folder2/file.html'));
        self::assertTrue($maker->matches('/folder3/file.html'));

        $maker->addSkippedUrlPattern('/folder2/');

        self::assertFalse($maker->matches('/folder1/file.html'));
        self::assertFalse($maker->matches('/folder2/file.html'));
        self::assertTrue($maker->matches('/folder3/file.html'));
    }

    public function testMatchesRootNoTheme(): void
    {
        $url = '/';

        $maker = $this->createMatchedUrlDecisionMaker([]);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->willReturn(null);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        self::assertTrue($maker->matches($url));
    }

    public function testMatchesRootNotOldTheme(): void
    {
        $url = '/';

        $maker = $this->createMatchedUrlDecisionMaker([]);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $themeName = 'default_51';
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with($themeName, ['default_50', 'default_51', 'default_60'])
            ->willReturn(false);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        self::assertTrue($maker->matches($url));
    }

    public function testMatchesRootNoSlug(): void
    {
        $url = '/';

        $maker = $this->createMatchedUrlDecisionMaker([]);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $themeName = 'default_51';
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with($themeName, ['default_50', 'default_51', 'default_60'])
            ->willReturn(true);

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn(null);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        self::assertTrue($maker->matches($url));
    }

    /**
     * @dataProvider getMatchesRootNotSupportedSlugDataProvider
     */
    public function testMatchesRootNotSupportedSlug(?Slug $slug): void
    {
        $url = '/';

        $maker = $this->createMatchedUrlDecisionMaker([]);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $themeName = 'default_51';
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with($themeName, ['default_50', 'default_51', 'default_60'])
            ->willReturn(true);

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn($slug);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        self::assertTrue($maker->matches($url));
    }

    public function getMatchesRootNotSupportedSlugDataProvider(): array
    {
        return [
            [null],
            [(new Slug())->setRouteName('route_name')],
        ];
    }

    public function testMatchesRootHomepageNotExists(): void
    {
        $url = '/';

        $maker = $this->createMatchedUrlDecisionMaker([]);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $themeName = 'default_51';
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with($themeName, ['default_50', 'default_51', 'default_60'])
            ->willReturn(true);

        $slug = (new Slug())->setRouteName('oro_cms_frontend_page_view');

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn($slug);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::HOME_PAGE))
            ->willReturn(null);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        self::assertTrue($maker->matches($url));
    }

    /**
     * @dataProvider getMatchesRootNotHomepageDataProvider
     */
    public function testMatchesRootNotHomepage(Slug $slug): void
    {
        $url = '/';

        $maker = $this->createMatchedUrlDecisionMaker([]);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $themeName = 'default_51';
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with($themeName, ['default_50', 'default_51', 'default_60'])
            ->willReturn(true);

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn($slug);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::HOME_PAGE))
            ->willReturn(1);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        self::assertTrue($maker->matches($url));
    }

    public function getMatchesRootNotHomepageDataProvider(): array
    {
        return [
            [(new Slug())->setRouteName('oro_cms_frontend_page_view')],
            [(new Slug())->setRouteName('oro_cms_frontend_page_view')->setRouteParameters(['foo' => 'bar'])],
            [(new Slug())->setRouteName('oro_cms_frontend_page_view')->setRouteParameters(['id' => \PHP_INT_MAX])],
        ];
    }

    public function testMatchesRootHomepage(): void
    {
        $url = '/';

        $maker = $this->createMatchedUrlDecisionMaker([]);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $themeName = 'default_51';
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with($themeName, ['default_50', 'default_51', 'default_60'])
            ->willReturn(true);

        $homepageId = 1;
        $slug = (new Slug())
            ->setRouteName('oro_cms_frontend_page_view')
            ->setRouteParameters(['id' => $homepageId]);

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn($slug);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::HOME_PAGE))
            ->willReturn($homepageId);

        $this->frontendHelper->expects(self::never())
            ->method('isFrontendUrl')
            ->withAnyParameters();

        self::assertFalse($maker->matches($url));
    }

    public function testMatchesRootNoInstalledApplication(): void
    {
        $url = '/';

        $maker = $this->createMatchedUrlDecisionMaker([]);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        self::assertTrue($maker->matches($url));
    }

    private function createMatchedUrlDecisionMaker(array $skippedUrlPatterns): MatchedUrlDecisionMaker
    {
        return new MatchedUrlDecisionMaker(
            $skippedUrlPatterns,
            $this->frontendHelper,
            $this->themeConfigurationProvider,
            $this->themeManager,
            $this->slugEntityFinder,
            $this->configManager,
            $this->applicationState
        );
    }
}
