<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider as BasePreviewMetadataProvider;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Decorates preview metadata provider by adding digitalAssetId and future file UUID elements to metadata.
 */
class PreviewMetadataProvider extends BasePreviewMetadataProvider
{
    /** @var BasePreviewMetadataProvider */
    private $innerPreviewMetadataProvider;

    /**
     * @param BasePreviewMetadataProvider $innerPreviewMetadataProvider
     */
    public function __construct(BasePreviewMetadataProvider $innerPreviewMetadataProvider)
    {
        $this->innerPreviewMetadataProvider = $innerPreviewMetadataProvider;
    }

    /**
     * @param File $file
     *
     * @return array
     */
    public function getMetadata(File $file): array
    {
        $metadata = $this->innerPreviewMetadataProvider->getMetadata($file);

        if ($file->getParentEntityClass() === DigitalAsset::class) {
            $metadata['digitalAssetId'] = $file->getParentEntityId();
        }

        // This identifier will be used as UUID for the future file.
        $metadata['uuid'] = UUIDGenerator::v4();

        return $metadata;
    }
}
