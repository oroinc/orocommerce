<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Provider\PreviewMetadataProvider;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider as BasePreviewMetadataProvider;

class PreviewMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var BasePreviewMetadataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $innerPreviewMetadataProvider;

    /** @var PreviewMetadataProvider */
    private $provider;

    protected function setUp()
    {
        $this->innerPreviewMetadataProvider = $this->createMock(BasePreviewMetadataProvider::class);

        $this->provider = new PreviewMetadataProvider($this->innerPreviewMetadataProvider);
    }

    public function testGetMetadataWhenNotDigitalAsset(): void
    {
        $file = new File();
        $file->setMimeType($mimeType = 'sample/type');

        $this->innerPreviewMetadataProvider
            ->expects($this->once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($innerMetadata = ['sampleKey' => 'sampleValue']);

        $metadata = $this->provider->getMetadata($file);
        $this->assertArraySubset($innerMetadata, $metadata);
        $this->assertArrayHasKey('uuid', $metadata);
    }

    public function testGetMetadataWhenDigitalAsset(): void
    {
        $file = new File();
        $file->setMimeType($mimeType = 'sample/type');
        $file->setParentEntityClass(DigitalAsset::class);
        $file->setParentEntityId($digitalAssetId = 10);

        $this->innerPreviewMetadataProvider
            ->expects($this->once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($innerMetadata = ['sampleKey' => 'sampleValue']);

        $metadata = $this->provider->getMetadata($file);
        $this->assertArraySubset($innerMetadata, $metadata);
        $this->assertArrayHasKey('uuid', $metadata);
        $this->assertArrayHasKey('digitalAssetId', $metadata);
        $this->assertEquals($digitalAssetId, $metadata['digitalAssetId']);
    }
}
