<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Twig;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Bundle\WebCatalogBundle\Twig\WebCatalogExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;

class WebCatalogExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ContentNodeTreeHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $treeHandler;

    /** @var ContentVariantTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $contentVariantTypeRegistry;

    /** @var WebCatalogExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->treeHandler = $this->getMockBuilder(ContentNodeTreeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contentVariantTypeRegistry = $this->getMockBuilder(ContentVariantTypeRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add(ContentNodeTreeHandler::class, $this->treeHandler)
            ->add(ContentVariantTypeRegistry::class, $this->contentVariantTypeRegistry)
            ->getContainer($this);

        $this->extension = new WebCatalogExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(WebCatalogExtension::NAME, $this->extension->getName());
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

        $this->assertEquals(
            $nodes,
            self::callTwigFunction($this->extension, 'oro_web_catalog_tree', [$webCatalog])
        );
    }

    public function testGetContentVariantTitle()
    {
        $typeName = 'type';
        $title = 'Title';

        $type = $this->createMock(ContentVariantTypeInterface::class);
        $type->expects($this->once())
            ->method('getTitle')
            ->willReturn($title);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->with($typeName)
            ->willReturn($type);

        $this->assertEquals(
            $title,
            self::callTwigFunction($this->extension, 'oro_web_catalog_content_variant_title', [$typeName])
        );
    }
}
