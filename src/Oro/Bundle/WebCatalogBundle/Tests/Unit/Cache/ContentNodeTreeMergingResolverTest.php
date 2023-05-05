<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeMergingResolver;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedContentNodesMerger;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeMergingResolverTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject $innerResolver;

    private ResolvedContentNodesMerger|\PHPUnit\Framework\MockObject\MockObject $resolvedContentNodesMerger;

    private ContentNodeTreeCache|\PHPUnit\Framework\MockObject\MockObject $mergedContentNodeTreeCache;

    private ContentNodeTreeMergingResolver $resolver;

    protected function setUp(): void
    {
        $this->innerResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->resolvedContentNodesMerger = $this->createMock(ResolvedContentNodesMerger::class);
        $this->mergedContentNodeTreeCache = $this->createMock(ContentNodeTreeCache::class);

        $this->resolver = new ContentNodeTreeMergingResolver(
            $this->innerResolver,
            $this->resolvedContentNodesMerger,
            $this->mergedContentNodeTreeCache
        );
    }

    public function testWithMultipleScopesWhenMergedCacheIsNull(): void
    {
        $nodeId = 2;
        $scopeId1 = 5;
        $scopeId2 = 10;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => $scopeId2]);

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1, $scopeId2])
            ->willReturn(null);

        $this->innerResolver
            ->expects(self::never())
            ->method('getResolvedContentNode');

        $this->resolvedContentNodesMerger
            ->expects(self::never())
            ->method('mergeResolvedNodes');

        $this->mergedContentNodeTreeCache
            ->expects(self::never())
            ->method('save');

        self::assertNull($this->resolver->getResolvedContentNode($node, [$scope1, $scope2]));
    }

    public function testWithMultipleScopesWhenMergedCacheExist(): void
    {
        $nodeId = 2;
        $scopeId1 = 5;
        $scopeId2 = 10;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => $scopeId2]);

        $resolvedNode = $this->createMock(ResolvedContentNode::class);

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1, $scopeId2])
            ->willReturn($resolvedNode);

        $this->innerResolver
            ->expects(self::never())
            ->method('getResolvedContentNode');

        $this->resolvedContentNodesMerger
            ->expects(self::never())
            ->method('mergeResolvedNodes');

        $this->mergedContentNodeTreeCache
            ->expects(self::never())
            ->method('save');

        self::assertSame($resolvedNode, $this->resolver->getResolvedContentNode($node, [$scope1, $scope2]));
    }

    public function testWithMultipleScopesWithTreeDepthWhenMergedCacheExist(): void
    {
        $nodeId = 2;
        $scopeId1 = 5;
        $scopeId2 = 10;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => $scopeId2]);

        $resolvedNode = $this->createMock(ResolvedContentNode::class);

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1, $scopeId2], 1)
            ->willReturn($resolvedNode);

        $this->innerResolver
            ->expects(self::never())
            ->method('getResolvedContentNode');

        $this->resolvedContentNodesMerger
            ->expects(self::never())
            ->method('mergeResolvedNodes');

        $this->mergedContentNodeTreeCache
            ->expects(self::never())
            ->method('save');

        self::assertSame(
            $resolvedNode,
            $this->resolver->getResolvedContentNode($node, [$scope1, $scope2], ['tree_depth' => 1])
        );
    }

    public function testWithMultipleScopesWhenMergedCacheNotExist(): void
    {
        $rootNodeId = 1;
        $nodeId = 2;
        $scopeId1 = 5;
        $scopeId2 = 10;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => $scopeId2]);

        $resolvedNodeFromScope1 = $this->createResolvedNode(
            2,
            [$this->createResolvedNode(3), $this->createResolvedNode(5)]
        );
        $resolvedNodeFromScope2 = $this->createResolvedNode(
            2,
            [$this->createResolvedNode(4, [$this->createResolvedNode(6)])]
        );

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1, $scopeId2], -1)
            ->willReturn(false);

        $this->innerResolver
            ->expects(self::exactly(2))
            ->method('getResolvedContentNode')
            ->withConsecutive(
                [$node, [$scope1], ['tree_depth' => -1]],
                [$node, [$scope2], ['tree_depth' => -1]]
            )
            ->willReturnOnConsecutiveCalls($resolvedNodeFromScope1, $resolvedNodeFromScope2);

        $mergedResolvedContentNode = $this->createResolvedNode(
            2,
            [
                $this->createResolvedNode(3),
                $this->createResolvedNode(4, [$this->createResolvedNode(6)]),
                $this->createResolvedNode(5),
            ]
        );

        $this->resolvedContentNodesMerger
            ->expects(self::once())
            ->method('mergeResolvedNodes')
            ->with([$resolvedNodeFromScope1, $resolvedNodeFromScope2])
            ->willReturn([$nodeId => $mergedResolvedContentNode]);

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('save')
            ->with($nodeId, [$scopeId1, $scopeId2], $mergedResolvedContentNode)
            ->willReturn(true);

        self::assertEquals(
            $mergedResolvedContentNode,
            $this->resolver->getResolvedContentNode($node, [$scope1, $scope2])
        );
    }

    public function testWithMultipleScopesWithTreeDepthWhenMergedCacheNotExist(): void
    {
        $rootNodeId = 1;
        $nodeId = 2;
        $scopeId1 = 5;
        $scopeId2 = 10;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => $scopeId2]);

        $resolvedNodeFromScope1 = $this->createResolvedNode(
            1,
            [$this->createResolvedNode(2, [$this->createResolvedNode(3), $this->createResolvedNode(5)])]
        );
        $resolvedNodeFromScope2 = $this->createResolvedNode(
            1,
            [$this->createResolvedNode(2, [$this->createResolvedNode(4, [$this->createResolvedNode(6)])])]
        );

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1, $scopeId2], 1)
            ->willReturn(false);

        $this->innerResolver
            ->expects(self::exactly(2))
            ->method('getResolvedContentNode')
            ->withConsecutive([$node, [$scope1], ['tree_depth' => -1]], [$node, [$scope2], ['tree_depth' => -1]])
            ->willReturnOnConsecutiveCalls($resolvedNodeFromScope1, $resolvedNodeFromScope2);

        $mergedResolvedContentNode = $this->createResolvedNode(
            2,
            [
                $this->createResolvedNode(3),
                $this->createResolvedNode(4, [$this->createResolvedNode(6)]),
                $this->createResolvedNode(5),
            ]
        );

        $this->resolvedContentNodesMerger
            ->expects(self::once())
            ->method('mergeResolvedNodes')
            ->with([$resolvedNodeFromScope1, $resolvedNodeFromScope2])
            ->willReturn([$nodeId => $mergedResolvedContentNode]);

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('save')
            ->with($nodeId, [$scopeId1, $scopeId2], $mergedResolvedContentNode)
            ->willReturn(true);

        $mergedResolvedContentNodeWithAppliedDepth = $this->createResolvedNode(
            2,
            [
                $this->createResolvedNode(3),
                $this->createResolvedNode(4),
                $this->createResolvedNode(5),
            ]
        );

        self::assertEquals(
            $mergedResolvedContentNodeWithAppliedDepth,
            $this->resolver->getResolvedContentNode($node, [$scope1, $scope2], ['tree_depth' => 1])
        );
    }

    public function testWithMultipleScopesWhenMergedCacheNotExistAndNoResolvedNodes(): void
    {
        $rootNodeId = 1;
        $nodeId = 2;
        $scopeId1 = 5;
        $scopeId2 = 10;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => $scopeId2]);

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1, $scopeId2], -1)
            ->willReturn(false);

        $this->innerResolver
            ->expects(self::exactly(2))
            ->method('getResolvedContentNode')
            ->withConsecutive([$node, [$scope1], ['tree_depth' => -1]], [$node, [$scope2], ['tree_depth' => -1]])
            ->willReturn(null);

        $this->resolvedContentNodesMerger
            ->expects(self::never())
            ->method('mergeResolvedNodes');

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('save')
            ->with($nodeId, [$scopeId1, $scopeId2], null)
            ->willReturn(true);

        self::assertNull($this->resolver->getResolvedContentNode($node, [$scope1, $scope2]));
    }

    public function testWithSingleScopeAndNotRootNode(): void
    {
        $rootNodeId = 1;
        $nodeId = 2;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $resolvedNodeFromScope1 = $this->createResolvedNode(
            2,
            [$this->createResolvedNode(3), $this->createResolvedNode(5)]
        );

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1], -1)
            ->willReturn(false);

        $this->innerResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($node, [$scope1], ['tree_depth' => -1])
            ->willReturn($resolvedNodeFromScope1);

        $this->resolvedContentNodesMerger
            ->expects(self::never())
            ->method('mergeResolvedNodes');

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('save')
            ->with($nodeId, [$scopeId1], $resolvedNodeFromScope1)
            ->willReturn(true);

        self::assertEquals(
            $resolvedNodeFromScope1,
            $this->resolver->getResolvedContentNode($node, [$scope1])
        );
    }

    public function testWithSingleScopeWithTreeDepthAndNotRootNode(): void
    {
        $rootNodeId = 1;
        $nodeId = 2;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $resolvedNodeFromScope1 = $this->createResolvedNode(
            1,
            [$this->createResolvedNode(2, [$this->createResolvedNode(3), $this->createResolvedNode(5)])]
        );

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1], 1)
            ->willReturn(false);

        $this->innerResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($node, [$scope1], ['tree_depth' => -1])
            ->willReturn($resolvedNodeFromScope1);

        $this->resolvedContentNodesMerger
            ->expects(self::never())
            ->method('mergeResolvedNodes');

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('save')
            ->with($nodeId, [$scopeId1], $resolvedNodeFromScope1)
            ->willReturn(true);

        self::assertEquals(
            $resolvedNodeFromScope1,
            $this->resolver->getResolvedContentNode($node, [$scope1], ['tree_depth' => 1])
        );
    }

    public function testWithSingleScopeAndRootNode(): void
    {
        $rootNodeId = $nodeId = 1;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $resolvedNodeFromScope1 = $this->createResolvedNode(
            2,
            [$this->createResolvedNode(3), $this->createResolvedNode(5)]
        );

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1], -1)
            ->willReturn(false);

        $this->innerResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($node, [$scope1], ['tree_depth' => -1])
            ->willReturn($resolvedNodeFromScope1);

        $this->resolvedContentNodesMerger
            ->expects(self::never())
            ->method('mergeResolvedNodes');

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('save')
            ->with($nodeId, [$scopeId1], $resolvedNodeFromScope1)
            ->willReturn(true);

        self::assertEquals(
            $resolvedNodeFromScope1,
            $this->resolver->getResolvedContentNode($node, [$scope1])
        );
    }

    public function testWithSingleScopeWithTreeDepth(): void
    {
        $rootNodeId = $nodeId = 1;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $resolvedNodeFromScope1 = $this->createResolvedNode(
            1,
            [$this->createResolvedNode(2, [$this->createResolvedNode(3), $this->createResolvedNode(5)])]
        );

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($nodeId, [$scopeId1], 1)
            ->willReturn(false);

        $this->innerResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($node, [$scope1], ['tree_depth' => -1])
            ->willReturn($resolvedNodeFromScope1);

        $this->resolvedContentNodesMerger
            ->expects(self::never())
            ->method('mergeResolvedNodes');

        $this->mergedContentNodeTreeCache
            ->expects(self::once())
            ->method('save')
            ->with($nodeId, [$scopeId1], $resolvedNodeFromScope1)
            ->willReturn(true);

        self::assertEquals(
            $resolvedNodeFromScope1,
            $this->resolver->getResolvedContentNode($node, [$scope1], ['tree_depth' => 1])
        );
    }

    private function createResolvedNode(int $id, array $childNodes = []): ResolvedContentNode
    {
        $resolvedNode = new ResolvedContentNode(
            $id,
            'sample_identifier_' . $id,
            $id,
            new ArrayCollection(),
            new ResolvedContentVariant()
        );

        foreach ($childNodes as $childNode) {
            $resolvedNode->addChildNode($childNode);
        }

        return $resolvedNode;
    }
}
