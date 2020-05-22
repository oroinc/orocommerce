<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeResolver;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeResolverTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $innerResolver;

    /** @var ContentNodeTreeCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ContentNodeTreeResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->innerResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->cache = $this->createMock(ContentNodeTreeCache::class);

        $this->resolver = new ContentNodeTreeResolver($this->innerResolver, $this->cache);
    }

    public function testGetResolvedContentNodeWhenCachedDataExist()
    {
        $nodeId = 2;
        $scopeId = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        $resolvedNode = $this->createMock(ResolvedContentNode::class);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($nodeId, $scopeId)
            ->willReturn($resolvedNode);
        $this->innerResolver->expects($this->never())
            ->method('getResolvedContentNode');
        $this->cache->expects($this->never())
            ->method('save');

        $this->assertSame($resolvedNode, $this->resolver->getResolvedContentNode($node, $scope));
    }

    public function testGetResolvedContentNodeWhenNoCachedData()
    {
        $nodeId = 2;
        $scopeId = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        $resolvedNode = $this->createMock(ResolvedContentNode::class);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($nodeId, $scopeId)
            ->willReturn(false);
        $this->innerResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($this->identicalTo($node), $this->identicalTo($scope))
            ->willReturn($resolvedNode);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($nodeId, $scopeId, $this->identicalTo($resolvedNode));

        $this->assertSame($resolvedNode, $this->resolver->getResolvedContentNode($node, $scope));
    }

    public function testGetResolvedContentNodeWhenCacheIsEmpty()
    {
        $nodeId = 2;
        $scopeId = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($nodeId, $scopeId)
            ->willReturn(null);
        $this->innerResolver->expects($this->never())
            ->method('getResolvedContentNode');
        $this->cache->expects($this->never())
            ->method('save');

        $this->assertNull($this->resolver->getResolvedContentNode($node, $scope));
    }

    public function testGetResolvedContentNodeWhenCacheIsEmptyAndInnerResolverReturnsNull()
    {
        $nodeId = 2;
        $scopeId = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($nodeId, $scopeId)
            ->willReturn(false);
        $this->innerResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($this->identicalTo($node), $this->identicalTo($scope))
            ->willReturn(null);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($nodeId, $scopeId, $this->isNull());

        $this->assertNull($this->resolver->getResolvedContentNode($node, $scope));
    }
}
