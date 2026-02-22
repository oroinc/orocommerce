<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Twig;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Bundle\WebCatalogBundle\Twig\WebCatalogExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebCatalogExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ContentNodeTreeHandler&MockObject $treeHandler;
    private ContentVariantTypeRegistry&MockObject $contentVariantTypeRegistry;
    private WebCatalogExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->treeHandler = $this->createMock(ContentNodeTreeHandler::class);
        $this->contentVariantTypeRegistry = $this->createMock(ContentVariantTypeRegistry::class);

        $container = self::getContainerBuilder()
            ->add(ContentNodeTreeHandler::class, $this->treeHandler)
            ->add(ContentVariantTypeRegistry::class, $this->contentVariantTypeRegistry)
            ->getContainer($this);

        $this->extension = new WebCatalogExtension($container);
    }

    public function testGetNodesTree()
    {
        $root = new ContentNode();
        $node = new ContentNode();
        $nodes = [$root, $node];
        $webCatalog = new WebCatalog();

        $this->treeHandler->expects(self::once())
            ->method('getTreeRootByWebCatalog')
            ->willReturn($root);
        $this->treeHandler->expects(self::once())
            ->method('createTree')
            ->with($root, true)
            ->willReturn($nodes);

        self::assertEquals(
            $nodes,
            self::callTwigFunction($this->extension, 'oro_web_catalog_tree', [$webCatalog])
        );
    }

    public function testGetContentVariantTitle()
    {
        $typeName = 'type';
        $title = 'Title';

        $type = $this->createMock(ContentVariantTypeInterface::class);
        $type->expects(self::once())
            ->method('getTitle')
            ->willReturn($title);

        $this->contentVariantTypeRegistry->expects(self::once())
            ->method('getContentVariantType')
            ->with($typeName)
            ->willReturn($type);

        self::assertEquals(
            $title,
            self::callTwigFunction($this->extension, 'oro_web_catalog_content_variant_title', [$typeName])
        );
    }
}
