<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Oro\Bundle\CMSBundle\Twig\ContentTemplateImageExtension;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContentTemplateImageExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private const PLACEHOLDER = 'placeholder/image.png';

    public function testGetPreviewImagePlaceholder(): void
    {
        $extension = $this->createExtensionWithRelativePaths();

        self::assertEquals(
            '/content_template_preview_small/placeholder/image.png',
            self::callTwigFunction(
                $extension,
                'content_template_preview_image_placeholder',
                ['content_template_preview_small']
            )
        );
    }

    public function testGetPreviewImagePlaceholderWithFormat(): void
    {
        $extension = $this->createExtensionWithRelativePaths();

        self::assertEquals(
            '/content_template_preview_small/placeholder/image.png.webp',
            self::callTwigFunction(
                $extension,
                'content_template_preview_image_placeholder',
                ['content_template_preview_small', 'webp']
            )
        );
    }

    public function testGetPreviewImagePlaceholderWithAbsoluteUrl(): void
    {
        $extension = $this->createExtensionWithAbsolutePaths();

        self::assertEquals(
            'https://example.com/content_template_preview_small/placeholder/image.png',
            self::callTwigFunction(
                $extension,
                'content_template_preview_image_placeholder',
                ['content_template_preview_small']
            )
        );
    }

    public function testGetPreviewImagePlaceholderWithAbsoluteUrlAndFormat(): void
    {
        $extension = $this->createExtensionWithAbsolutePaths();

        self::assertEquals(
            'https://example.com/content_template_preview_small/placeholder/image.png.webp',
            self::callTwigFunction(
                $extension,
                'content_template_preview_image_placeholder',
                ['content_template_preview_small', 'webp']
            )
        );
    }

    /**
     * Creates extension with mocks configured for relative paths (ABSOLUTE_PATH).
     */
    private function createExtensionWithRelativePaths(): ContentTemplateImageExtension
    {
        $imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $imagePlaceholderProvider->expects(self::any())
            ->method('getPath')
            ->willReturnCallback(static function (string $filter, string $format) {
                return '/' . $filter . '/' . self::PLACEHOLDER . ($format ? '.' . $format : '');
            });

        $apiUrlResolver = $this->createMock(ApiUrlResolver::class);
        $apiUrlResolver->expects(self::any())
            ->method('getEffectiveReferenceType')
            ->willReturn(UrlGeneratorInterface::ABSOLUTE_PATH);

        return $this->createExtension($imagePlaceholderProvider, $apiUrlResolver);
    }

    /**
     * Creates extension with mocks configured for absolute URLs (ABSOLUTE_URL).
     */
    private function createExtensionWithAbsolutePaths(): ContentTemplateImageExtension
    {
        $imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $imagePlaceholderProvider->expects(self::any())
            ->method('getPath')
            ->willReturnCallback(static function (string $filter, string $format) {
                return 'https://example.com/' . $filter . '/' . self::PLACEHOLDER . ($format ? '.' . $format : '');
            });

        $apiUrlResolver = $this->createMock(ApiUrlResolver::class);
        $apiUrlResolver->expects(self::any())
            ->method('getEffectiveReferenceType')
            ->willReturn(UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->createExtension($imagePlaceholderProvider, $apiUrlResolver);
    }

    private function createExtension(
        ImagePlaceholderProviderInterface $imagePlaceholderProvider,
        ApiUrlResolver $apiUrlResolver
    ): ContentTemplateImageExtension {
        $container = self::getContainerBuilder()
            ->add('oro_cms.provider.content_template_preview_image_placeholder', $imagePlaceholderProvider)
            ->add(ApiUrlResolver::class, $apiUrlResolver)
            ->getContainer($this);

        return new ContentTemplateImageExtension($container);
    }
}
