<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCachingResolver;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeCachingResolverTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject $innerResolver;

    private ContentNodeTreeCache|\PHPUnit\Framework\MockObject\MockObject $rootContentNodeTreeCache;

    private ContentNodeTreeCachingResolver $resolver;

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $this->innerResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->rootContentNodeTreeCache = $this->createMock(ContentNodeTreeCache::class);

        $this->resolver = new ContentNodeTreeCachingResolver(
            $this->innerResolver,
            $managerRegistry,
            $this->rootContentNodeTreeCache
        );

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
    }

    public function testWhenRootCacheIsNull(): void
    {
        $rootNodeId = $nodeId = 1;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $this->rootContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($rootNodeId, [$scopeId1])
            ->willReturn(null);

        $this->innerResolver
            ->expects(self::never())
            ->method('getResolvedContentNode');

        $this->rootContentNodeTreeCache
            ->expects(self::never())
            ->method('save');

        self::assertNull($this->resolver->getResolvedContentNode($node, [$scope1]));
    }

    public function testWhenRootCacheExist(): void
    {
        $rootNodeId = $nodeId = 1;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $resolvedRootNode = $this->createResolvedNode(
            1,
            [$this->createResolvedNode(2, [$this->createResolvedNode(4, [$this->createResolvedNode(6)])])]
        );

        $this->rootContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($rootNodeId, [$scopeId1])
            ->willReturn($resolvedRootNode);

        $this->innerResolver
            ->expects(self::never())
            ->method('getResolvedContentNode');

        $this->rootContentNodeTreeCache
            ->expects(self::never())
            ->method('save');

        self::assertSame($resolvedRootNode, $this->resolver->getResolvedContentNode($node, [$scope1]));
    }

    public function testWithTreeDepthWhenRootCacheExist(): void
    {
        $rootNodeId = 1;
        $nodeId = 2;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $resolvedRootNode = $this->createResolvedNode(
            1,
            [$this->createResolvedNode(2, [$this->createResolvedNode(4, [$this->createResolvedNode(6)])])]
        );

        $this->rootContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($rootNodeId, [$scopeId1], -1)
            ->willReturn($resolvedRootNode);

        $this->innerResolver
            ->expects(self::never())
            ->method('getResolvedContentNode');

        $this->rootContentNodeTreeCache
            ->expects(self::never())
            ->method('save');

        self::assertSame(
            $resolvedRootNode->getChildNodes()[0],
            $this->resolver->getResolvedContentNode($node, [$scope1], ['tree_depth' => 1])
        );
    }

    public function testWhenRootCacheNotExist(): void
    {
        $rootNodeId = $nodeId = 1;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $resolvedRootNode = $this->createResolvedNode(
            1,
            [$this->createResolvedNode(2, [$this->createResolvedNode(4, [$this->createResolvedNode(6)])])]
        );

        $this->rootContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($rootNodeId, [$scopeId1], -1)
            ->willReturn(false);

        /** @var ContentNode $rootNode */
        $rootNode = $this->getEntity(ContentNode::class, ['id' => $rootNodeId]);
        $this->entityManager
            ->expects(self::atLeastOnce())
            ->method('find')
            ->with(ContentNode::class, $rootNodeId)
            ->willReturn($rootNode);

        $this->innerResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($rootNode, [$scope1], ['tree_depth' => -1])
            ->willReturn($resolvedRootNode);

        $this->rootContentNodeTreeCache
            ->expects(self::once())
            ->method('save')
            ->with($rootNodeId, [$scopeId1], $resolvedRootNode)
            ->willReturn(true);

        self::assertSame(
            $resolvedRootNode,
            $this->resolver->getResolvedContentNode($node, [$scope1])
        );
    }

    public function testWithTreeDepthWhenRootCacheNotExist(): void
    {
        $rootNodeId = 1;
        $nodeId = 2;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $resolvedRootNode = $this->createResolvedNode(
            1,
            [$this->createResolvedNode(2, [$this->createResolvedNode(4, [$this->createResolvedNode(6)])])]
        );

        $this->rootContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($rootNodeId, [$scopeId1], -1)
            ->willReturn(false);

        /** @var ContentNode $rootNode */
        $rootNode = $this->getEntity(ContentNode::class, ['id' => $rootNodeId]);
        $this->entityManager
            ->expects(self::atLeastOnce())
            ->method('find')
            ->with(ContentNode::class, $rootNodeId)
            ->willReturn($rootNode);

        $this->innerResolver
            ->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($rootNode, [$scope1], ['tree_depth' => -1])
            ->willReturn($resolvedRootNode);

        $this->rootContentNodeTreeCache
            ->expects(self::once())
            ->method('save')
            ->with($rootNodeId, [$scopeId1], $resolvedRootNode)
            ->willReturn(true);

        self::assertSame(
            $resolvedRootNode->getChildNodes()[0],
            $this->resolver->getResolvedContentNode($node, [$scope1], ['tree_depth' => 1])
        );
    }

    public function testWhenRootCacheNotExistAndNoRoot(): void
    {
        $rootNodeId = 1;
        $nodeId = 2;
        $scopeId1 = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId, 'root' => $rootNodeId]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => $scopeId1]);

        $this->rootContentNodeTreeCache
            ->expects(self::once())
            ->method('fetch')
            ->with($rootNodeId, [$scopeId1], -1)
            ->willReturn(false);

        $this->entityManager
            ->expects(self::atLeastOnce())
            ->method('find')
            ->with(ContentNode::class, $rootNodeId)
            ->willReturn(null);

        $this->innerResolver
            ->expects(self::never())
            ->method('getResolvedContentNode');

        $this->rootContentNodeTreeCache
            ->expects(self::never())
            ->method('save');

        self::assertNull($this->resolver->getResolvedContentNode($node, [$scope1], ['tree_depth' => 1]));
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
