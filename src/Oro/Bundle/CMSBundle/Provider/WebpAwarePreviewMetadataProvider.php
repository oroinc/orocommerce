<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProviderInterface;

/**
 * Decorates preview metadata provider by adding the url for image in webp format for wysiwyg.
 */
class WebpAwarePreviewMetadataProvider implements PreviewMetadataProviderInterface
{
    private PreviewMetadataProviderInterface $innerPreviewMetadataProvider;

    private AttachmentManager $attachmentManager;

    private MimeTypeChecker $mimeTypeChecker;

    public function __construct(
        PreviewMetadataProviderInterface $innerPreviewMetadataProvider,
        AttachmentManager $attachmentManager,
        MimeTypeChecker $mimeTypeChecker
    ) {
        $this->innerPreviewMetadataProvider = $innerPreviewMetadataProvider;
        $this->attachmentManager = $attachmentManager;
        $this->mimeTypeChecker = $mimeTypeChecker;
    }

    public function getMetadata(File $file): array
    {
        $metadata = $this->innerPreviewMetadataProvider->getMetadata($file);

        if (!$this->attachmentManager->isWebpDisabled()
            && $this->mimeTypeChecker->isImageMimeType((string)$file->getMimeType())) {
            $metadata['url_webp'] = $this->attachmentManager->getFilteredImageUrl($file, 'wysiwyg_original', 'webp');
        }

        return $metadata;
    }
}
