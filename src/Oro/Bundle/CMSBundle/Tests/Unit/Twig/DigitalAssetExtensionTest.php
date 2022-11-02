<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Provider\FileUrlByUuidProvider;
use Oro\Bundle\CMSBundle\Twig\DigitalAssetExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DigitalAssetExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private FileUrlByUuidProvider|\PHPUnit\Framework\MockObject\MockObject $fileUrlByUuidProvider;

    private DigitalAssetExtension $extension;

    protected function setUp(): void
    {
        $this->fileUrlByUuidProvider = $this->createMock(FileUrlByUuidProvider::class);

        $container = self::getContainerBuilder()
            ->add(FileUrlByUuidProvider::class, $this->fileUrlByUuidProvider)
            ->getContainer($this);

        $this->extension = new DigitalAssetExtension($container);
    }

    public function testGetWysiwygImageUrl(): void
    {
        $this->fileUrlByUuidProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with('file-uuid', 'test-filter', 'sample_format', 1)
            ->willReturn('/url');

        self::assertSame(
            '/url',
            self::callTwigFunction($this->extension, 'wysiwyg_image', [42, 'file-uuid', 'test-filter', 'sample_format'])
        );
    }

    public function testGetWysiwygFileUrl(): void
    {
        $this->fileUrlByUuidProvider->expects(self::once())
            ->method('getFileUrl')
            ->with('file-uuid', 'download', 1)
            ->willReturn('/url');

        self::assertSame(
            '/url',
            self::callTwigFunction($this->extension, 'wysiwyg_file', [42, 'file-uuid'])
        );
    }
}
