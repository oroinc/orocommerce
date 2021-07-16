<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider as InnerPreviewMetadataProvider;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProviderInterface;

/**
 * Decorates preview metadata provider by adding image/file url for wysiwyg.
 */
class PreviewMetadataProvider implements PreviewMetadataProviderInterface
{
    /** @var InnerPreviewMetadataProvider */
    private $innerPreviewMetadataProvider;

    /** @var FileUrlProviderInterface */
    private $fileUrlProvider;

    /** @var MimeTypeChecker */
    private $mimeTypeChecker;

    public function __construct(
        InnerPreviewMetadataProvider $innerPreviewMetadataProvider,
        FileUrlProviderInterface $fileUrlProvider,
        MimeTypeChecker $mimeTypeChecker
    ) {
        $this->innerPreviewMetadataProvider = $innerPreviewMetadataProvider;
        $this->fileUrlProvider = $fileUrlProvider;
        $this->mimeTypeChecker = $mimeTypeChecker;
    }

    public function getMetadata(File $file): array
    {
        $metadata = $this->innerPreviewMetadataProvider->getMetadata($file);

        if ($this->mimeTypeChecker->isImageMimeType((string)$file->getMimeType())) {
            $metadata['url'] = $this->fileUrlProvider->getFilteredImageUrl($file, 'wysiwyg_original');
        } else {
            $metadata['url'] = $this->fileUrlProvider
                ->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD);
        }

        return $metadata;
    }
}
