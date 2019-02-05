<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Twig;

use Oro\Bundle\CatalogBundle\Twig\CategoryImageExtension;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class CategoryImageExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagePlaceholderProvider;

    /** @var CategoryImageExtension */
    private $extension;

    public function setUp()
    {
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_catalog.provider.category_image_placeholder', $this->imagePlaceholderProvider)
            ->getContainer($this);

        $this->extension = new CategoryImageExtension($container);
    }

    public function testGetCategoryImagePlaceholder(): void
    {
        $filter = 'category_medium';
        $path = '/some/test/path.npg';

        $this->imagePlaceholderProvider->expects($this->once())
            ->method('getPath')
            ->with($filter)
            ->willReturn($path);

        $this->assertEquals(
            $path,
            self::callTwigFunction($this->extension, 'category_image_placeholder', [$filter])
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_catalog_category_image_extension', $this->extension->getName());
    }
}
