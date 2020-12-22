<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\CMSBundle\Provider\PreviewMetadataProvider;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider as BasePreviewMetadataProvider;

class PreviewMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var BasePreviewMetadataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $innerPreviewMetadataProvider;

    /** @var FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileUrlProvider;

    /** @var MimeTypeChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $mimeTypeChecker;

    /** @var PreviewMetadataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->innerPreviewMetadataProvider = $this->createMock(BasePreviewMetadataProvider::class);
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);
        $this->mimeTypeChecker = $this->createMock(MimeTypeChecker::class);

        $this->provider = new PreviewMetadataProvider(
            $this->innerPreviewMetadataProvider,
            $this->fileUrlProvider,
            $this->mimeTypeChecker
        );
    }

    public function testGetMetadataWhenNotImage(): void
    {
        $file = new File();
        $file->setMimeType($mimeType = 'sample/type');

        $this->innerPreviewMetadataProvider
            ->expects($this->once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($innerMetadata = ['sampleKey' => 'sampleValue']);

        $this->mimeTypeChecker
            ->expects($this->once())
            ->method('isImageMimeType')
            ->with($mimeType)
            ->willReturn(false);

        $fileUrl = '/sample/file/url';
        $this->fileUrlProvider
            ->expects($this->once())
            ->method('getFileUrl')
            ->with($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD)
            ->willReturn($fileUrl);

        $metadata = $this->provider->getMetadata($file);
        $this->assertEquals(['sampleKey' => 'sampleValue', 'url' => $fileUrl], $metadata);
    }

    public function testGetMetadataWhenImage(): void
    {
        $file = new File();
        $file->setMimeType($mimeType = 'sample/type');

        $this->innerPreviewMetadataProvider
            ->expects($this->once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($innerMetadata = ['sampleKey' => 'sampleValue']);

        $this->mimeTypeChecker
            ->expects($this->once())
            ->method('isImageMimeType')
            ->with($mimeType)
            ->willReturn(true);

        $imageUrl = '/sample/file/url';
        $this->fileUrlProvider
            ->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($file, 'wysiwyg_original')
            ->willReturn($imageUrl);

        $metadata = $this->provider->getMetadata($file);
        $this->assertEquals(['sampleKey' => 'sampleValue', 'url' => $imageUrl], $metadata);
    }
}
