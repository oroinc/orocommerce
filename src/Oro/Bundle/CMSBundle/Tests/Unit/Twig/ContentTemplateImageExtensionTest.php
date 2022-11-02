<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\Twig\ContentTemplateImageExtension;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ContentTemplateImageExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const PLACEHOLDER = 'placeholder/image.png';

    private ContentTemplateImageExtension $extension;

    protected function setUp(): void
    {
        $imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $imagePlaceholderProvider->expects(self::any())
            ->method('getPath')
            ->willReturnCallback(static function (string $filter, string $format) {
                return '/' . $filter . '/' . self::PLACEHOLDER . ($format ? '.' . $format : '');
            });

        $container = self::getContainerBuilder()
            ->add('oro_cms.provider.content_template_preview_image_placeholder', $imagePlaceholderProvider)
            ->getContainer($this);

        $this->extension = new ContentTemplateImageExtension($container);
    }

    public function testGetPreviewImagePlaceholder(): void
    {
        self::assertEquals(
            '/content_template_preview_small/placeholder/image.png',
            self::callTwigFunction(
                $this->extension,
                'content_template_preview_image_placeholder',
                ['content_template_preview_small']
            )
        );
    }

    public function testGetPreviewImagePlaceholderWithFormat(): void
    {
        self::assertEquals(
            '/content_template_preview_small/placeholder/image.png.webp',
            self::callTwigFunction(
                $this->extension,
                'content_template_preview_image_placeholder',
                ['content_template_preview_small', 'webp']
            )
        );
    }
}
