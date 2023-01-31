<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Menu;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Menu\CompositeMenuContentNodesProvider;
use Oro\Bundle\WebCatalogBundle\Menu\MenuContentNodesProviderInterface;

class CompositeMenuContentNodesProviderTest extends \PHPUnit\Framework\TestCase
{
    private MenuContentNodesProviderInterface|\PHPUnit\Framework\MockObject\MockObject $menuContentNodesProvider;

    private MenuContentNodesProviderInterface|\PHPUnit\Framework\MockObject\MockObject
        $menuContentNodesFrontendProvider;

    private FrontendHelper|\PHPUnit\Framework\MockObject\MockObject $frontendHelper;

    private CompositeMenuContentNodesProvider $provider;

    protected function setUp(): void
    {
        $this->menuContentNodesProvider = $this->createMock(MenuContentNodesProviderInterface::class);
        $this->menuContentNodesFrontendProvider = $this->createMock(MenuContentNodesProviderInterface::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->provider = new CompositeMenuContentNodesProvider(
            $this->menuContentNodesProvider,
            $this->menuContentNodesFrontendProvider,
            $this->frontendHelper
        );
    }

    public function testGetResolvedContentNodeWhenStorefront(): void
    {
        $contentNode = new ContentNode();
        $context = ['sample_key' => 'sample_value'];

        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->menuContentNodesProvider
            ->expects(self::never())
            ->method(self::anything());

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->menuContentNodesFrontendProvider
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, $context)
            ->willReturn($resolvedContentNode);

        self::assertEquals($resolvedContentNode, $this->provider->getResolvedContentNode($contentNode, $context));
    }

    public function testGetResolvedContentNodeWhenNotStorefront(): void
    {
        $contentNode = new ContentNode();
        $context = ['sample_key' => 'sample_value'];

        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->menuContentNodesFrontendProvider
            ->expects(self::never())
            ->method(self::anything());

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->menuContentNodesProvider
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, $context)
            ->willReturn($resolvedContentNode);

        self::assertEquals($resolvedContentNode, $this->provider->getResolvedContentNode($contentNode, $context));
    }
}
