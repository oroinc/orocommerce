<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlByUuidProvider;
use Oro\Bundle\CMSBundle\Twig\DigitalAssetExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DigitalAssetExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private FileUrlByUuidProvider&MockObject $fileUrlByUuidProvider;
    private ApiUrlResolver&MockObject $apiUrlResolver;

    private DigitalAssetExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileUrlByUuidProvider = $this->createMock(FileUrlByUuidProvider::class);
        $this->apiUrlResolver = $this->createMock(ApiUrlResolver::class);

        $container = self::getContainerBuilder()
            ->add(FileUrlByUuidProvider::class, $this->fileUrlByUuidProvider)
            ->add('oro_api.api_url_resolver', $this->apiUrlResolver)
            ->getContainer($this);

        $this->extension = new DigitalAssetExtension($container);
    }

    public function testGetWysiwygImageUrl(): void
    {
        $this->apiUrlResolver->expects(self::once())
            ->method('getEffectiveReferenceType')
            ->with(UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(UrlGeneratorInterface::ABSOLUTE_PATH);

        $this->fileUrlByUuidProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with('file-uuid', 'test-filter', 'sample_format', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');

        self::assertSame(
            '/url',
            self::callTwigFunction($this->extension, 'wysiwyg_image', [42, 'file-uuid', 'test-filter', 'sample_format'])
        );
    }

    public function testGetWysiwygFileUrl(): void
    {
        $this->apiUrlResolver->expects(self::once())
            ->method('getEffectiveReferenceType')
            ->with(UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(UrlGeneratorInterface::ABSOLUTE_PATH);

        $this->fileUrlByUuidProvider->expects(self::once())
            ->method('getFileUrl')
            ->with('file-uuid', 'download', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');

        self::assertSame(
            '/url',
            self::callTwigFunction($this->extension, 'wysiwyg_file', [42, 'file-uuid'])
        );
    }

    public function testGetWysiwygImageUrlWhenAbsoluteUrlsRequired(): void
    {
        $this->apiUrlResolver->expects(self::once())
            ->method('getEffectiveReferenceType')
            ->with(UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(UrlGeneratorInterface::ABSOLUTE_URL);

        $this->fileUrlByUuidProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with('file-uuid', 'test-filter', 'sample_format', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/url');

        self::assertSame(
            'https://example.com/url',
            self::callTwigFunction($this->extension, 'wysiwyg_image', [42, 'file-uuid', 'test-filter', 'sample_format'])
        );
    }

    public function testGetWysiwygFileUrlWhenAbsoluteUrlsRequired(): void
    {
        $this->apiUrlResolver->expects(self::once())
            ->method('getEffectiveReferenceType')
            ->with(UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(UrlGeneratorInterface::ABSOLUTE_URL);

        $this->fileUrlByUuidProvider->expects(self::once())
            ->method('getFileUrl')
            ->with('file-uuid', 'download', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/file.pdf');

        self::assertSame(
            'https://example.com/file.pdf',
            self::callTwigFunction($this->extension, 'wysiwyg_file', [42, 'file-uuid'])
        );
    }

    public function testGetWysiwygImageUrlWithExplicitReferenceType(): void
    {
        $this->apiUrlResolver->expects(self::never())
            ->method('getEffectiveReferenceType');

        $this->fileUrlByUuidProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with('file-uuid', 'test-filter', 'sample_format', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/url');

        self::assertSame(
            'https://example.com/url',
            self::callTwigFunction(
                $this->extension,
                'wysiwyg_image',
                [42, 'file-uuid', 'test-filter', 'sample_format', UrlGeneratorInterface::ABSOLUTE_URL]
            )
        );
    }

    public function testGetWysiwygFileUrlWithExplicitReferenceType(): void
    {
        $this->apiUrlResolver->expects(self::never())
            ->method('getEffectiveReferenceType');

        $this->fileUrlByUuidProvider->expects(self::once())
            ->method('getFileUrl')
            ->with('file-uuid', 'download', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/file.pdf');

        self::assertSame(
            'https://example.com/file.pdf',
            self::callTwigFunction(
                $this->extension,
                'wysiwyg_file',
                [42, 'file-uuid', 'download', UrlGeneratorInterface::ABSOLUTE_URL]
            )
        );
    }
}
