<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Twig;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Bundle\WebCatalogBundle\Twig\WebCatalogExtension;

class WebCatalogExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentNodeTreeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $treeHandler;

    /**
     * @var WebCatalogExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->treeHandler = $this->getMockBuilder(ContentNodeTreeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new WebCatalogExtension($this->treeHandler);
    }

    public function testGetName()
    {
        $this->assertEquals(WebCatalogExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $expected = [
            new \Twig_SimpleFunction('oro_web_catalog_tree', [$this->extension, 'getNodesTree']),
        ];
        $this->assertEquals($expected, $this->extension->getFunctions());
    }

    public function testGetNodesTree()
    {
        $root = new ContentNode();
        $node = new ContentNode();
        $nodes = [$root, $node];
        $webCatalog = new WebCatalog();
        $this->treeHandler->expects($this->once())
            ->method('getTreeRootByWebCatalog')
            ->willReturn($root);
        $this->treeHandler->expects($this->once())
            ->method('createTree')
            ->with($root, true)
            ->willReturn($nodes);
        $this->assertEquals($nodes, $this->extension->getNodesTree($webCatalog));
    }
}
