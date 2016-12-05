<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Twig;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Bundle\WebCatalogBundle\Twig\WebCatalogExtension;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;

class WebCatalogExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentNodeTreeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $treeHandler;

    /**
     * @var ContentVariantTypeRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentVariantTypeRegistry;

    /**
     * @var WebCatalogExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->treeHandler = $this->getMockBuilder(ContentNodeTreeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contentVariantTypeRegistry = $this->getMockBuilder(ContentVariantTypeRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new WebCatalogExtension($this->treeHandler, $this->contentVariantTypeRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals(WebCatalogExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $expected = [
            new \Twig_SimpleFunction('oro_web_catalog_tree', [$this->extension, 'getNodesTree']),
            new \Twig_SimpleFunction(
                'oro_web_catalog_content_variant_title',
                [$this->extension, 'getContentVariantTitle']
            ),
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

    public function testGetContentVariantTitle()
    {
        $typeName = 'type';
        $title = 'Title';

        $type = $this->getMock(ContentVariantTypeInterface::class);
        $type->expects($this->once())
            ->method('getTitle')
            ->willReturn($title);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->with($typeName)
            ->willReturn($type);

        $this->assertEquals($title, $this->extension->getContentVariantTitle($typeName));
    }
}
