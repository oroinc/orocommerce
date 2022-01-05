<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\CMSBundle\Provider\WebpAwarePreviewMetadataProvider;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider as BasePreviewMetadataProvider;

class WebpAwarePreviewMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    private BasePreviewMetadataProvider|\PHPUnit\Framework\MockObject\MockObject $innerPreviewMetadataProvider;

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private MimeTypeChecker|\PHPUnit\Framework\MockObject\MockObject $mimeTypeChecker;

    private WebpAwarePreviewMetadataProvider $provider;

    protected function setUp(): void
    {
        $this->innerPreviewMetadataProvider = $this->createMock(BasePreviewMetadataProvider::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->mimeTypeChecker = $this->createMock(MimeTypeChecker::class);

        $this->provider = new WebpAwarePreviewMetadataProvider(
            $this->innerPreviewMetadataProvider,
            $this->attachmentManager,
            $this->mimeTypeChecker
        );
    }

    public function testGetMetadataReturnsUnchangedWhenNotImage(): void
    {
        $file = new File();
        $file->setMimeType($mimeType = 'sample/type');

        $innerMetadata = ['sampleKey' => 'sampleValue'];
        $this->innerPreviewMetadataProvider
            ->expects(self::once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($innerMetadata);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpDisabled')
            ->willReturn(false);

        $this->mimeTypeChecker
            ->expects(self::once())
            ->method('isImageMimeType')
            ->with($mimeType)
            ->willReturn(false);

        $metadata = $this->provider->getMetadata($file);
        self::assertEquals($innerMetadata, $metadata);
    }

    public function testGetMetadataReturnsUnchangedWhenDisabled(): void
    {
        $file = new File();
        $file->setMimeType($mimeType = 'sample/type');

        $innerMetadata = ['sampleKey' => 'sampleValue'];
        $this->innerPreviewMetadataProvider
            ->expects(self::once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($innerMetadata);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpDisabled')
            ->willReturn(true);

        $this->mimeTypeChecker
            ->expects(self::never())
            ->method('isImageMimeType');

        $metadata = $this->provider->getMetadata($file);
        self::assertEquals($innerMetadata, $metadata);
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

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpDisabled')
            ->willReturn(false);

        $this->mimeTypeChecker
            ->expects(self::once())
            ->method('isImageMimeType')
            ->with($mimeType)
            ->willReturn(true);

        $imageUrl = '/sample/file/url.webp';
        $this->attachmentManager
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, 'wysiwyg_original', 'webp')
            ->willReturn($imageUrl);

        $metadata = $this->provider->getMetadata($file);
        self::assertEquals(['sampleKey' => 'sampleValue', 'url_webp' => $imageUrl], $metadata);
    }
}
