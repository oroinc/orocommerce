<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheDumper;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub\ContentNodeStub;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub\Scope;

class ContentNodeTreeCacheDumperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeTreeResolver;

    /** @var ContentNodeTreeCache|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeTreeCache;

    /** @var ContentNodeRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeRepository;

    /** @var WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogRepository;

    private ContentNodeTreeCacheDumper $dumper;

    protected function setUp(): void
    {
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->contentNodeTreeCache = $this->createMock(ContentNodeTreeCache::class);
        $this->contentNodeRepository = $this->createMock(ContentNodeRepository::class);
        $this->webCatalogRepository = $this->createMock(WebCatalogRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [ContentNode::class, null, $this->contentNodeRepository],
                [WebCatalog::class, null, $this->webCatalogRepository]
            ]);

        $this->dumper = new ContentNodeTreeCacheDumper(
            $this->contentNodeTreeResolver,
            $this->contentNodeTreeCache,
            $doctrine
        );
    }

    public function testDump(): void
    {
        $node = new ContentNodeStub(2);
        $scope = (new Scope())->setId(5);

        $this->contentNodeTreeCache->expects(self::once())
            ->method('delete')
            ->with($node->getId(), $scope->getId());
        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with(self::identicalTo($node), self::identicalTo($scope))
            ->willReturn($this->createMock(ResolvedContentNode::class));

        $this->dumper->dump($node, $scope);
    }

    public function testDumpForAllScopes(): void
    {
        $webCatalog = new WebCatalog();
        $node = new ContentNodeStub(2);
        $scope = (new Scope())->setId(5);

        $this->contentNodeRepository->expects(self::once())
            ->method('getRootNodeByWebCatalog')
            ->with(self::identicalTo($webCatalog))
            ->willReturn($node);
        $this->webCatalogRepository->expects(self::once())
            ->method('getUsedScopes')
            ->with(self::identicalTo($webCatalog))
            ->willReturn([$scope]);

        $this->contentNodeTreeCache->expects(self::once())
            ->method('delete')
            ->with($node->getId(), $scope->getId());
        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with(self::identicalTo($node), self::identicalTo($scope))
            ->willReturn($this->createMock(ResolvedContentNode::class));

        $this->dumper->dumpForAllScopes($webCatalog);
    }

    public function testDumpForAllScopesWhenNoRootNode(): void
    {
        $webCatalog = new WebCatalog();

        $this->contentNodeRepository
            ->expects(self::once())
            ->method('getRootNodeByWebCatalog')
            ->with(self::identicalTo($webCatalog))
            ->willReturn(null);

        $this->webCatalogRepository
            ->expects(self::never())
            ->method('getUsedScopes');

        $this->contentNodeTreeCache
            ->expects(self::never())
            ->method('delete');

        $this->contentNodeTreeResolver
            ->expects(self::never())
            ->method('getResolvedContentNode');

        $this->dumper->dumpForAllScopes($webCatalog);
    }
}
