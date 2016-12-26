<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils;

use Oro\Bundle\ScopeBundle\Entity\Scope;
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
     * @var ContentNodeTreeResolverFacade
     */
    private $facade;

    protected function setUp()
    {
        $this->defaultResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->cachedResolver = $this->createMock(ContentNodeTreeResolverInterface::class);

        $this->facade = new ContentNodeTreeResolverFacade(
            $this->defaultResolver,
            $this->cachedResolver
        );
    }

    /**
     * @dataProvider supportsDataProvider
     * @param bool $defaultSupport
     * @param bool $cacheSupport
     * @param bool $expected
     */
    public function testSupports($defaultSupport, $cacheSupport, $expected)
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);

        $this->defaultResolver->expects($this->any())
            ->method('supports')
            ->willReturn($defaultSupport);
        $this->cachedResolver->expects($this->any())
            ->method('supports')
            ->willReturn($cacheSupport);

        $this->assertEquals($expected, $this->facade->supports($node, $scope));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'cache support' => [false, true, true],
            'default support' => [true, false, true],
            'both support' => [true, true, true],
            'both not support' => [false, false, false]
        ];
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
        $this->cachedResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($node, $scope)
            ->willReturn($resolvedNode);

        $this->defaultResolver->expects($this->never())
            ->method('supports');

        $this->assertSame($resolvedNode, $this->facade->getResolvedContentNode($node, $scope));
    }

    public function testGetResolvedContentNodeByDefault()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);
        $resolvedNode = $this->getMockBuilder(ResolvedContentNode::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cachedResolver->expects($this->any())
            ->method('supports')
            ->willReturn(false);
        $this->cachedResolver->expects($this->never())
            ->method('getResolvedContentNode');

        $this->defaultResolver->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $this->defaultResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($node, $scope)
            ->willReturn($resolvedNode);

        $this->assertSame($resolvedNode, $this->facade->getResolvedContentNode($node, $scope));
    }

    public function testGetResolvedContentNodeByNone()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);

        $this->cachedResolver->expects($this->any())
            ->method('supports')
            ->willReturn(false);
        $this->cachedResolver->expects($this->never())
            ->method('getResolvedContentNode');

        $this->defaultResolver->expects($this->once())
            ->method('supports')
            ->willReturn(false);
        $this->defaultResolver->expects($this->never())
            ->method('getResolvedContentNode');

        $this->assertNull($this->facade->getResolvedContentNode($node, $scope));
    }
}
