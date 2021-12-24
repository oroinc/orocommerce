<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\CMSBundle\Provider\PreviewMetadataProvider;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProviderInterface;
use Symfony\Component\Mime\MimeTypesInterface;

class PreviewMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    private PreviewMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerPreviewMetadataProvider;

    private FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject $fileUrlProvider;

    private MimeTypeChecker|\PHPUnit\Framework\MockObject\MockObject $mimeTypeChecker;

    private MimeTypesInterface|\PHPUnit\Framework\MockObject\MockObject $mimeTypes;

    private PreviewMetadataProvider $provider;

    protected function setUp(): void
    {
        $this->innerPreviewMetadataProvider = $this->createMock(PreviewMetadataProviderInterface::class);
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);
        $this->mimeTypeChecker = $this->createMock(MimeTypeChecker::class);
        $this->mimeTypes = $this->createMock(MimeTypesInterface::class);

        $this->provider = new PreviewMetadataProvider(
            $this->innerPreviewMetadataProvider,
            $this->fileUrlProvider,
            $this->mimeTypeChecker,
            $this->mimeTypes
        );
    }

    public function testGetMetadataWhenNotImage(): void
    {
        $file = new File();
        $file->setMimeType($mimeType = 'sample/type');

        $this->innerPreviewMetadataProvider
            ->expects(self::once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn(['sampleKey' => 'sampleValue']);

        $this->mimeTypeChecker
            ->expects(self::once())
            ->method('isImageMimeType')
            ->with($mimeType)
            ->willReturn(false);

        $fileUrl = '/sample/file/url';
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getFileUrl')
            ->with($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD)
            ->willReturn($fileUrl);

        $metadata = $this->provider->getMetadata($file);
        self::assertEquals(['sampleKey' => 'sampleValue', 'url' => $fileUrl], $metadata);
    }

    public function testGetMetadataWhenImage(): void
    {
        $file = new File();
        $file->setMimeType($mimeType = 'sample/type');

        $this->innerPreviewMetadataProvider
            ->expects(self::once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn(['sampleKey' => 'sampleValue']);

        $this->mimeTypeChecker
            ->expects(self::once())
            ->method('isImageMimeType')
            ->with($mimeType)
            ->willReturn(true);

        $format = 'jpg';
        $this->mimeTypes
            ->expects(self::once())
            ->method('getExtensions')
            ->with($mimeType)
            ->willReturn([$format]);

        $imageUrl = '/sample/file/url';
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, 'wysiwyg_original', $format)
            ->willReturn($imageUrl);

        $metadata = $this->provider->getMetadata($file);
        self::assertEquals(['sampleKey' => 'sampleValue', 'url' => $imageUrl], $metadata);
    }

    public function testGetMetadataWhenImageButNoFormat(): void
    {
        $file = new File();
        $file->setMimeType($mimeType = 'sample/type');

        $this->innerPreviewMetadataProvider
            ->expects(self::once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn(['sampleKey' => 'sampleValue']);

        $this->mimeTypeChecker
            ->expects(self::once())
            ->method('isImageMimeType')
            ->with($mimeType)
            ->willReturn(true);

        $this->mimeTypes
            ->expects(self::once())
            ->method('getExtensions')
            ->with($mimeType)
            ->willReturn([]);

        $imageUrl = '/sample/file/url';
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, 'wysiwyg_original', '')
            ->willReturn($imageUrl);

        $metadata = $this->provider->getMetadata($file);
        self::assertEquals(['sampleKey' => 'sampleValue', 'url' => $imageUrl], $metadata);
    }
}
