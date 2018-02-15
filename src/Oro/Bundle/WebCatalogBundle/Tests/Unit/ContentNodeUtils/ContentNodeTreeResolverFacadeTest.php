<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\Dumper\ContentNodeTreeDumper;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverFacade;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeResolverFacadeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ContentNodeTreeResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $defaultResolver;

    /**
     * @var ContentNodeTreeResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cachedResolver;

    /**
     * @var ContentNodeTreeDumper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contentNodeTreeDumper;

    /**
     * @var ContentNodeTreeResolverFacade
     */
    private $facade;

    protected function setUp()
    {
        $this->defaultResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->cachedResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->contentNodeTreeDumper = $this->createMock(ContentNodeTreeDumper::class);

        $this->facade = new ContentNodeTreeResolverFacade(
            $this->defaultResolver,
            $this->cachedResolver
        );

        $this->facade->setContentNodeTreeDumper($this->contentNodeTreeDumper);
    }

    public function testSupports()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);

        $this->assertEquals(true, $this->facade->supports($node, $scope));
    }

    public function testGetResolvedContentNodeByCache()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);
        $resolvedNode = $this->getMockBuilder(ResolvedContentNode::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cachedResolver->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $this->contentNodeTreeDumper->expects($this->never())
            ->method('dump')
            ->with($node, $scope);
        $this->cachedResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($node, $scope)
            ->willReturn($resolvedNode);

        $this->assertSame($resolvedNode, $this->facade->getResolvedContentNode($node, $scope));
    }

    public function testGetResolvedContentNodeWithoutCache()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);

        $resolvedNode = $this->getMockBuilder(ResolvedContentNode::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cachedResolver->expects($this->once())
            ->method('supports')
            ->willReturn(false);
        $this->contentNodeTreeDumper->expects($this->once())
            ->method('dump')
            ->with($node, $scope);
        $this->cachedResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->willReturn($resolvedNode);

        $this->assertSame($resolvedNode, $this->facade->getResolvedContentNode($node, $scope));
    }
}
