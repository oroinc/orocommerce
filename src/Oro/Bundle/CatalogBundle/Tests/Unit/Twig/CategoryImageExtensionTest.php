<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CatalogBundle\Twig\CategoryImageExtension;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class CategoryImageExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagePlaceholderProvider;

    /** @var CategoryImageExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_attachment.manager', $this->attachmentManager)
            ->add('oro_catalog.provider.category_image_placeholder', $this->imagePlaceholderProvider)
            ->getContainer($this);

        $this->extension = new CategoryImageExtension($container);
    }

    public function testGetCategoryFilteredImage(): void
    {
        $file = new File();
        $filter = 'category_medium';

        $this->attachmentManager
            ->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($file, $filter)
            ->willReturn('/path/to/filtered/image');

        $this->imagePlaceholderProvider
            ->expects($this->never())
            ->method('getPath');

        $this->assertEquals(
            '/path/to/filtered/image',
            self::callTwigFunction($this->extension, 'category_filtered_image', [$file, $filter])
        );
    }

    public function testGetCategoryFilteredImageWithoutFile(): void
    {
        $filter = 'category_medium';
        $path = '/some/test/path.npg';

        $this->attachmentManager
            ->expects($this->never())
            ->method('getFilteredImageUrl');

        $this->imagePlaceholderProvider
            ->expects($this->once())
            ->method('getPath')
            ->with($filter)
            ->willReturn($path);

        $this->assertEquals(
            $path,
            self::callTwigFunction($this->extension, 'category_filtered_image', [null, $filter])
        );
    }

    public function testGetCategoryImagePlaceholder(): void
    {
        $filter = 'category_medium';
        $path = '/some/test/path.npg';

        $this->imagePlaceholderProvider
            ->expects($this->once())
            ->method('getPath')
            ->with($filter)
            ->willReturn($path);

        $this->assertEquals(
            $path,
            self::callTwigFunction($this->extension, 'category_image_placeholder', [$filter])
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro_catalog_category_image_extension', $this->extension->getName());
    }
}
