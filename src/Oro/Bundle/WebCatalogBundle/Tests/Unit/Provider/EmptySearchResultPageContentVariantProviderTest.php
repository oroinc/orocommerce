<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\EmptySearchResultPageContentVariantProvider;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity\Stub\ContentNode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmptySearchResultPageContentVariantProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;

    private ContentNodeTreeResolverInterface|MockObject $contentNodeTreeResolver;

    private RequestWebContentScopeProvider|MockObject $requestWebContentScopeProvider;

    private EmptySearchResultPageContentVariantProvider $emptySearchResultPageContentVariantProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->requestWebContentScopeProvider = $this->createMock(RequestWebContentScopeProvider::class);
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);

        $this->emptySearchResultPageContentVariantProvider = new EmptySearchResultPageContentVariantProvider(
            $this->configManager,
            $this->contentNodeTreeResolver,
            $this->requestWebContentScopeProvider
        );
    }

    public function testGetResolvedContentVariantNoEmptySearchResultPageConfigurationValue(): void
    {
        $this->requestWebContentScopeProvider->expects(self::never())
            ->method('getScopes');

        $this->contentNodeTreeResolver->expects(self::never())
            ->method('getResolvedContentNode')
            ->withAnyParameters();

        self::assertNull($this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant());
    }

    public function testGetResolvedContentVariantNoContentNode(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_web_catalog.empty_search_result_page')
            ->willReturn(['webCatalog' => new WebCatalog()]);

        $this->requestWebContentScopeProvider->expects(self::never())
            ->method('getScopes');

        $this->contentNodeTreeResolver->expects(self::never())
            ->method('getResolvedContentNode')
            ->withAnyParameters();

        self::assertNull($this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant());
    }

    public function testGetResolvedContentVariantNoResolvedContentNode(): void
    {
        $contentNodeId = 1;
        $contentNode = (new ContentNode())
            ->setId($contentNodeId);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_web_catalog.empty_search_result_page')
            ->willReturn(['webCatalog' => new WebCatalog(), 'contentNode' => $contentNode]);

        $scopes = [new Scope()];
        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn($scopes);

        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, $scopes, ['tree_depth' => 0])
            ->willReturn(null);

        self::assertNull($this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant());
    }

    public function testGetResolvedContentVariant(): void
    {
        $contentNodeId = 1;
        $contentNode = (new ContentNode())
            ->setId($contentNodeId);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_web_catalog.empty_search_result_page')
            ->willReturn(['webCatalog' => new WebCatalog(), 'contentNode' => $contentNode]);

        $scopes = [new Scope()];
        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn($scopes);

        $resolvedContentVariant = $this->createMock(ResolvedContentVariant::class);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $resolvedContentNode->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, $scopes, ['tree_depth' => 0])
            ->willReturn($resolvedContentNode);

        self::assertSame(
            $resolvedContentVariant,
            $this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant()
        );
    }
}
