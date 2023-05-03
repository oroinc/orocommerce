<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerGroupCriteriaProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolver;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentNodesLoader;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub;

class ContentNodeTreeResolverTest extends \PHPUnit\Framework\TestCase
{
    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private ContentNodeProvider|\PHPUnit\Framework\MockObject\MockObject $contentNodeProvider;

    private ScopeManager|\PHPUnit\Framework\MockObject\MockObject $scopeManager;

    private ResolvedContentNodesLoader|\PHPUnit\Framework\MockObject\MockObject $resolvedContentNodesLoader;

    private ContentNodeTreeResolver $resolver;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->contentNodeProvider = $this->createMock(ContentNodeProvider::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->resolvedContentNodesLoader = $this->createMock(ResolvedContentNodesLoader::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->resolver = new ContentNodeTreeResolver(
            $this->doctrineHelper,
            $this->contentNodeProvider,
            $this->scopeManager,
            $this->resolvedContentNodesLoader,
            $propertyAccessor
        );
    }

    public function testWhenNoScopes(): void
    {
        $contentNode = new ContentNode();

        $this->contentNodeProvider
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->resolver->getResolvedContentNode($contentNode, []));
    }

    public function testWhenNoContentNodeIds(): void
    {
        $scope = new Scope();
        $contentNode = (new Stub\ContentNodeStub(10))
            ->setLeft(2)
            ->setRight(42);

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteriaByScope')
            ->with($scope, 'web_content', [])
            ->willReturn($scopeCriteria);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with(ContentNode::class, 'node')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('node.left >= :left AND node.right <= :right')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['left', $contentNode->getLeft()], ['right', $contentNode->getRight()])
            ->willReturnSelf();

        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentNodeIds')
            ->with($queryBuilder, $scopeCriteria)
            ->willReturn([]);

        $this->contentNodeProvider
            ->expects(self::never())
            ->method('getContentVariantIds');

        $this->resolvedContentNodesLoader
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->resolver->getResolvedContentNode($contentNode, [$scope]));
    }

    public function testWhenNoContentVariantIds(): void
    {
        $scope = new Scope();
        $contentNode = (new Stub\ContentNodeStub(10))
            ->setLeft(2)
            ->setRight(42);

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteriaByScope')
            ->with($scope, 'web_content', [])
            ->willReturn($scopeCriteria);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with(ContentNode::class, 'node')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('node.left >= :left AND node.right <= :right')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['left', $contentNode->getLeft()], ['right', $contentNode->getRight()])
            ->willReturnSelf();

        $nodeIds = [10, 20, 30];
        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentNodeIds')
            ->with($queryBuilder, $scopeCriteria)
            ->willReturn($nodeIds);

        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentVariantIds')
            ->with($nodeIds, $scopeCriteria)
            ->willReturn([]);

        $this->resolvedContentNodesLoader
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->resolver->getResolvedContentNode($contentNode, [$scope]));
    }

    public function testWhenNoResolvedContentNode(): void
    {
        $scope = new Scope();
        $contentNode = (new Stub\ContentNodeStub(10))
            ->setLeft(2)
            ->setRight(42);

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteriaByScope')
            ->with($scope, 'web_content', [])
            ->willReturn($scopeCriteria);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with(ContentNode::class, 'node')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('node.left >= :left AND node.right <= :right')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['left', $contentNode->getLeft()], ['right', $contentNode->getRight()])
            ->willReturnSelf();

        $nodeIds = [10, 20, 30];
        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentNodeIds')
            ->with($queryBuilder, $scopeCriteria)
            ->willReturn($nodeIds);

        $variantIds = [10 => 101, 20 => 201, 30 => 301];
        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentVariantIds')
            ->with($nodeIds, $scopeCriteria)
            ->willReturn($variantIds);

        $this->resolvedContentNodesLoader
            ->expects(self::once())
            ->method('loadResolvedContentNodes')
            ->with($variantIds)
            ->willReturn([]);

        self::assertNull($this->resolver->getResolvedContentNode($contentNode, [$scope]));
    }

    public function testWhenHasResolvedContentNode(): void
    {
        $scope = new Scope();
        $contentNode = (new Stub\ContentNodeStub(10))
            ->setLeft(2)
            ->setRight(42);

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteriaByScope')
            ->with($scope, 'web_content', [])
            ->willReturn($scopeCriteria);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with(ContentNode::class, 'node')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('node.left >= :left AND node.right <= :right')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['left', $contentNode->getLeft()], ['right', $contentNode->getRight()])
            ->willReturnSelf();

        $nodeIds = [10, 20, 30];
        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentNodeIds')
            ->with($queryBuilder, $scopeCriteria)
            ->willReturn($nodeIds);

        $variantIds = [10 => 101, 20 => 201, 30 => 301];
        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentVariantIds')
            ->with($nodeIds, $scopeCriteria)
            ->willReturn($variantIds);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->resolvedContentNodesLoader
            ->expects(self::once())
            ->method('loadResolvedContentNodes')
            ->with($variantIds)
            ->willReturn([10 => $resolvedContentNode]);

        self::assertSame($resolvedContentNode, $this->resolver->getResolvedContentNode($contentNode, [$scope]));
    }

    public function testWithTreeDepthWhenHasResolvedContentNode(): void
    {
        $scope = new Scope();
        $contentNode = (new Stub\ContentNodeStub(10))
            ->setLeft(2)
            ->setRight(42)
            ->setLevel(2);
        $context['tree_depth'] = 3;

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteriaByScope')
            ->with($scope, 'web_content', [])
            ->willReturn($scopeCriteria);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with(ContentNode::class, 'node')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('node.left >= :left AND node.right <= :right')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('node.level <= :max_level')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::exactly(3))
            ->method('setParameter')
            ->withConsecutive(
                ['left', $contentNode->getLeft()],
                ['right', $contentNode->getRight()],
                ['max_level', $contentNode->getLevel() + $context['tree_depth']]
            )
            ->willReturnSelf();

        $nodeIds = [10, 20, 30];
        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentNodeIds')
            ->with($queryBuilder, $scopeCriteria)
            ->willReturn($nodeIds);

        $variantIds = [10 => 101, 20 => 201, 30 => 301];
        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentVariantIds')
            ->with($nodeIds, $scopeCriteria)
            ->willReturn($variantIds);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->resolvedContentNodesLoader
            ->expects(self::once())
            ->method('loadResolvedContentNodes')
            ->with($variantIds)
            ->willReturn([10 => $resolvedContentNode]);

        self::assertSame(
            $resolvedContentNode,
            $this->resolver->getResolvedContentNode($contentNode, [$scope], $context)
        );
    }

    public function testWithCustomerScopeWhenHasResolvedContentNode(): void
    {
        $customer = (new Customer())
            ->setGroup(new CustomerGroup());
        $scope = (new Stub\Scope())
            ->setId(10)
            ->setCustomer($customer);
        $contentNode = (new Stub\ContentNodeStub(10))
            ->setLeft(2)
            ->setRight(42);

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteriaByScope')
            ->with($scope, 'web_content', [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customer->getGroup()])
            ->willReturn($scopeCriteria);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with(ContentNode::class, 'node')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('node.left >= :left AND node.right <= :right')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['left', $contentNode->getLeft()], ['right', $contentNode->getRight()])
            ->willReturnSelf();

        $nodeIds = [10, 20, 30];
        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentNodeIds')
            ->with($queryBuilder, $scopeCriteria)
            ->willReturn($nodeIds);

        $variantIds = [10 => 101, 20 => 201, 30 => 301];
        $this->contentNodeProvider
            ->expects(self::once())
            ->method('getContentVariantIds')
            ->with($nodeIds, $scopeCriteria)
            ->willReturn($variantIds);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->resolvedContentNodesLoader
            ->expects(self::once())
            ->method('loadResolvedContentNodes')
            ->with($variantIds)
            ->willReturn([10 => $resolvedContentNode]);

        self::assertSame(
            $resolvedContentNode,
            $this->resolver->getResolvedContentNode($contentNode, [$scope])
        );
    }

    public function testWithMultipleScopes(): void
    {
        $scope1 = new Scope();
        $scope2 = new Scope();
        $contentNode = (new Stub\ContentNodeStub(10))
            ->setLeft(2)
            ->setRight(42);

        $scopeCriteria1 = $this->createMock(ScopeCriteria::class);
        $scopeCriteria2 = $this->createMock(ScopeCriteria::class);
        $this->scopeManager
            ->expects(self::exactly(2))
            ->method('getCriteriaByScope')
            ->withConsecutive([$scope1, 'web_content', []], [$scope2, 'web_content', []])
            ->willReturnOnConsecutiveCalls($scopeCriteria1, $scopeCriteria2);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with(ContentNode::class, 'node')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('node.left >= :left AND node.right <= :right')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['left', $contentNode->getLeft()], ['right', $contentNode->getRight()])
            ->willReturnSelf();

        $nodeIds1 = [10, 20, 30];
        $nodeIds2 = [10, 25, 40];
        $this->contentNodeProvider
            ->expects(self::exactly(2))
            ->method('getContentNodeIds')
            ->withConsecutive([$queryBuilder, $scopeCriteria1], [$queryBuilder, $scopeCriteria2])
            ->willReturnOnConsecutiveCalls($nodeIds1, $nodeIds2);

        $variantIds1 = [10 => 101, 20 => 201, 30 => 301];
        $variantIds2 = [10 => 102, 25 => 251, 20 => 201];
        $this->contentNodeProvider
            ->expects(self::exactly(2))
            ->method('getContentVariantIds')
            ->withConsecutive([$nodeIds1, $scopeCriteria1], [$nodeIds2, $scopeCriteria2])
            ->willReturnOnConsecutiveCalls($variantIds1, $variantIds2);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->resolvedContentNodesLoader
            ->expects(self::once())
            ->method('loadResolvedContentNodes')
            ->with([10 => 101, 25 => 251, 20 => 201, 30 => 301])
            ->willReturn([10 => $resolvedContentNode]);

        self::assertSame(
            $resolvedContentNode,
            $this->resolver->getResolvedContentNode($contentNode, [$scope1, $scope2])
        );
    }
}
