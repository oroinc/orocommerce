<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration as CMSConfiguration;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\AccessibilityPageProvider;
use Oro\Bundle\CMSBundle\Provider\PageRoutingInformationProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Component\Routing\RouteData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AccessibilityPageProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private DoctrineHelper&MockObject $doctrineHelper;
    private PageRoutingInformationProvider&MockObject $pageRoutingInformationProvider;
    private UrlGeneratorInterface&MockObject $router;
    private LocalizationHelper&MockObject $localizationHelper;
    private ContentNodeTreeResolverInterface&MockObject $contentNodeTreeResolver;
    private RequestWebContentScopeProvider&MockObject $requestWebContentScopeProvider;
    private AccessibilityPageProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->pageRoutingInformationProvider = $this->createMock(PageRoutingInformationProvider::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->requestWebContentScopeProvider = $this->createMock(RequestWebContentScopeProvider::class);

        $this->provider = new AccessibilityPageProvider(
            $this->configManager,
            $this->doctrineHelper,
            $this->pageRoutingInformationProvider,
            $this->router,
            $this->localizationHelper,
            $this->contentNodeTreeResolver,
            $this->requestWebContentScopeProvider
        );
    }

    // getAccessibilityPageUrl — web catalog inactive

    public function testGetAccessibilityPageUrlReturnsNullWhenWebCatalogInactiveAndPageNotConfigured(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, null],
                [CMSConfiguration::getConfigKeyByName(CMSConfiguration::ACCESSIBILITY_PAGE), false, false, null, null],
            ]);

        self::assertNull($this->provider->getAccessibilityPageUrl());
    }

    public function testGetAccessibilityPageUrlReturnsCmsPageUrl(): void
    {
        $page = $this->createMock(Page::class);

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, null],
                [CMSConfiguration::getConfigKeyByName(CMSConfiguration::ACCESSIBILITY_PAGE), false, false, null, 42],
            ]);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($page);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(Page::class)
            ->willReturn($repository);

        $routeData = new RouteData('oro_cms_frontend_page_view', ['id' => 42]);
        $this->pageRoutingInformationProvider->expects(self::once())
            ->method('getRouteData')
            ->with($page)
            ->willReturn($routeData);

        $this->router->expects(self::once())
            ->method('generate')
            ->with('oro_cms_frontend_page_view', ['id' => 42])
            ->willReturn('/accessibility');

        self::assertSame('/accessibility', $this->provider->getAccessibilityPageUrl());
    }

    // getAccessibilityPageUrl — web catalog active

    public function testGetAccessibilityPageUrlReturnsNullWhenWebCatalogActiveAndNodeNotConfigured(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, null],
            ]);

        self::assertNull($this->provider->getAccessibilityPageUrl());
    }

    public function testGetAccessibilityPageUrlReturnsNullWhenWebCatalogActiveAndNodeNotFound(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, 5],
            ]);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn(null);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        self::assertNull($this->provider->getAccessibilityPageUrl());
    }

    public function testGetAccessibilityPageUrlReturnsNullWhenScopesEmpty(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, 5],
            ]);

        $contentNode = $this->createMock(ContentNode::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn($contentNode);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn([]);

        self::assertNull($this->provider->getAccessibilityPageUrl());
    }

    public function testGetAccessibilityPageUrlReturnsNullWhenResolvedNodeIsNull(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, 5],
            ]);

        $contentNode = $this->createMock(ContentNode::class);
        $scope = $this->createMock(Scope::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn($contentNode);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn([$scope]);

        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, [$scope], ['tree_depth' => 0])
            ->willReturn(null);

        self::assertNull($this->provider->getAccessibilityPageUrl());
    }

    public function testGetAccessibilityPageUrlReturnsNullWhenWebCatalogActiveAndNodeHasNoUrl(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, 5],
            ]);

        $contentNode = $this->createMock(ContentNode::class);
        $scope = $this->createMock(Scope::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn($contentNode);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn([$scope]);

        $resolvedVariant = new ResolvedContentVariant();
        $resolvedNode = new ResolvedContentNode(5, 'accessibility', 0, new ArrayCollection(), $resolvedVariant);

        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, [$scope], ['tree_depth' => 0])
            ->willReturn($resolvedNode);

        $localizedUrls = new ArrayCollection();
        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->with($localizedUrls)
            ->willReturn(null);

        self::assertNull($this->provider->getAccessibilityPageUrl());
    }

    public function testGetAccessibilityPageUrlReturnsContentNodeUrl(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, 5],
            ]);

        $contentNode = $this->createMock(ContentNode::class);
        $scope = $this->createMock(Scope::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn($contentNode);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn([$scope]);

        $localizedUrl = new LocalizedFallbackValue();
        $localizedUrl->setString('/accessibility-node');

        $resolvedVariant = new ResolvedContentVariant();
        $resolvedVariant->addLocalizedUrl($localizedUrl);
        $resolvedNode = new ResolvedContentNode(5, 'accessibility', 0, new ArrayCollection(), $resolvedVariant);

        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, [$scope], ['tree_depth' => 0])
            ->willReturn($resolvedNode);

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->willReturn($localizedUrl);

        $context = $this->createMock(RequestContext::class);
        $context->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('/app');

        $this->router->expects(self::once())
            ->method('getContext')
            ->willReturn($context);

        self::assertSame('/app/accessibility-node', $this->provider->getAccessibilityPageUrl());
    }

    // getAccessibilityPageTitle — web catalog inactive

    public function testGetAccessibilityPageTitleReturnsCmsPageTitle(): void
    {
        $page = $this->createMock(Page::class);

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, null],
                [CMSConfiguration::getConfigKeyByName(CMSConfiguration::ACCESSIBILITY_PAGE), false, false, null, 42],
            ]);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($page);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(Page::class)
            ->willReturn($repository);

        $title = new LocalizedFallbackValue();
        $title->setString('Accessibility');

        $titles = new ArrayCollection([$title]);
        $page->expects(self::once())
            ->method('getTitles')
            ->willReturn($titles);

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->with($titles)
            ->willReturn($title);

        self::assertSame('Accessibility', $this->provider->getAccessibilityPageTitle());
    }

    // getAccessibilityPageTitle — web catalog active

    public function testGetAccessibilityPageTitleReturnsEmptyStringWhenWebCatalogActiveAndNodeNotFound(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, 5],
            ]);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn(null);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        self::assertSame('', $this->provider->getAccessibilityPageTitle());
    }

    public function testGetAccessibilityPageTitleReturnsEmptyStringWhenScopesEmpty(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, 5],
            ]);

        $contentNode = $this->createMock(ContentNode::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn($contentNode);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn([]);

        self::assertSame('', $this->provider->getAccessibilityPageTitle());
    }

    public function testGetAccessibilityPageTitleReturnsEmptyStringWhenResolvedNodeIsNull(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, 5],
            ]);

        $contentNode = $this->createMock(ContentNode::class);
        $scope = $this->createMock(Scope::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn($contentNode);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn([$scope]);

        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, [$scope], ['tree_depth' => 0])
            ->willReturn(null);

        self::assertSame('', $this->provider->getAccessibilityPageTitle());
    }

    public function testGetAccessibilityPageTitleReturnsContentNodeTitle(): void
    {
        $nodeKey = Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [WebCatalogUsageProvider::SETTINGS_KEY, false, false, null, 1],
                [$nodeKey, false, false, null, 5],
            ]);

        $contentNode = $this->createMock(ContentNode::class);
        $scope = $this->createMock(Scope::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn($contentNode);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn([$scope]);

        $title = new LocalizedFallbackValue();
        $title->setString('Accessibility Node');

        $titles = new ArrayCollection([$title]);
        $resolvedVariant = new ResolvedContentVariant();
        $resolvedNode = new ResolvedContentNode(5, 'accessibility', 0, $titles, $resolvedVariant);

        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, [$scope], ['tree_depth' => 0])
            ->willReturn($resolvedNode);

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->with($titles)
            ->willReturn($title);

        self::assertSame('Accessibility Node', $this->provider->getAccessibilityPageTitle());
    }
}
