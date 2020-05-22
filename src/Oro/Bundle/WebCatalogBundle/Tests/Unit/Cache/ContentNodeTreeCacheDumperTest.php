<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheDumper;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeCacheDumperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeTreeResolver;

    /** @var ContentNodeTreeCache|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeTreeCache;

    /** @var ContentNodeRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeRepository;

    /** @var WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogRepository;

    /** @var ContentNodeTreeCacheDumper */
    private $dumper;

    protected function setUp(): void
    {
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->contentNodeTreeCache = $this->createMock(ContentNodeTreeCache::class);
        $this->contentNodeRepository = $this->createMock(ContentNodeRepository::class);
        $this->webCatalogRepository = $this->createMock(WebCatalogRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
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

    public function testDump()
    {
        $nodeId = 2;
        $scopeId = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        $this->contentNodeTreeCache->expects($this->once())
            ->method('delete')
            ->with($nodeId, $scopeId);
        $this->contentNodeTreeResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($this->identicalTo($node), $this->identicalTo($scope))
            ->willReturn($this->createMock(ResolvedContentNode::class));

        $this->dumper->dump($node, $scope);
    }

    public function testDumpForAllScopes()
    {
        $webCatalog = new WebCatalog();

        $nodeId = 2;
        $scopeId = 5;
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        $this->contentNodeRepository->expects($this->once())
            ->method('getRootNodeByWebCatalog')
            ->with($this->identicalTo($webCatalog))
            ->willReturn($node);
        $this->webCatalogRepository->expects($this->once())
            ->method('getUsedScopes')
            ->with($this->identicalTo($webCatalog))
            ->willReturn([$scope]);

        $this->contentNodeTreeCache->expects($this->once())
            ->method('delete')
            ->with($nodeId, $scopeId);
        $this->contentNodeTreeResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($this->identicalTo($node), $this->identicalTo($scope))
            ->willReturn($this->createMock(ResolvedContentNode::class));

        $this->dumper->dumpForAllScopes($webCatalog);
    }
}
