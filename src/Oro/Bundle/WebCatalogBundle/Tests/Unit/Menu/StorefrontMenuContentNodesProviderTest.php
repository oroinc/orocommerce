<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Menu;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Menu\StorefrontMenuContentNodesProvider;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;

class StorefrontMenuContentNodesProviderTest extends \PHPUnit\Framework\TestCase
{
    private RequestWebContentScopeProvider|\PHPUnit\Framework\MockObject\MockObject $requestWebContentScopeProvider;

    private ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject $contentNodeTreeResolver;

    private StorefrontMenuContentNodesProvider $provider;

    protected function setUp(): void
    {
        $this->requestWebContentScopeProvider = $this->createMock(RequestWebContentScopeProvider::class);
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);

        $this->provider = new StorefrontMenuContentNodesProvider(
            $this->requestWebContentScopeProvider,
            $this->contentNodeTreeResolver
        );
    }

    public function testWhenNoScopes(): void
    {
        $this->requestWebContentScopeProvider
            ->expects(self::once())
            ->method('getScopes')
            ->willReturn([]);

        $this->contentNodeTreeResolver
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getResolvedContentNode(new ContentNode()));
    }

    public function testWhenHasScopeAndNoResolvedContentNode(): void
    {
        $scope1 = new Scope();
        $this->requestWebContentScopeProvider
            ->expects(self::once())
            ->method('getScopes')
            ->willReturn([$scope1]);

        $contentNode = new ContentNode();
        $this->contentNodeTreeResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, [$scope1], ['tree_depth' => -1])
            ->willReturn(null);

        self::assertNull($this->provider->getResolvedContentNode($contentNode));
    }

    public function testWhenHasScopes(): void
    {
        $scope1 = new Scope();
        $scope2 = new Scope();
        $this->requestWebContentScopeProvider
            ->expects(self::once())
            ->method('getScopes')
            ->willReturn([$scope1, $scope2]);

        $contentNode = new ContentNode();
        $resolvedNode = $this->createMock(ResolvedContentNode::class);
        $this->contentNodeTreeResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, [$scope1, $scope2], ['tree_depth' => -1])
            ->willReturn($resolvedNode);

        self::assertSame($resolvedNode, $this->provider->getResolvedContentNode($contentNode));
    }

    public function testWithTreeDepth(): void
    {
        $scope1 = new Scope();
        $scope2 = new Scope();
        $this->requestWebContentScopeProvider
            ->expects(self::once())
            ->method('getScopes')
            ->willReturn([$scope1, $scope2]);

        $contentNode = new ContentNode();
        $resolvedNode = $this->createMock(ResolvedContentNode::class);
        $this->contentNodeTreeResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, [$scope1, $scope2], ['tree_depth' => 3])
            ->willReturn($resolvedNode);

        self::assertSame($resolvedNode, $this->provider->getResolvedContentNode($contentNode, ['tree_depth' => 3]));
    }
}
