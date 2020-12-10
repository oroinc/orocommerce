<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider as BasePreviewMetadataProvider;

/**
 * Decorates preview metadata provider by adding image/file url for wysiwyg.
 */
class PreviewMetadataProvider extends BasePreviewMetadataProvider
{
    /** @var BasePreviewMetadataProvider */
    private $innerPreviewMetadataProvider;

    /** @var FileUrlProviderInterface */
    private $fileUrlProvider;

    /** @var MimeTypeChecker */
    private $mimeTypeChecker;

    /**
     * @param BasePreviewMetadataProvider $innerPreviewMetadataProvider
     */
    public function __construct(BasePreviewMetadataProvider $innerPreviewMetadataProvider)
    {
        $this->innerPreviewMetadataProvider = $innerPreviewMetadataProvider;
    }

    /**
     * @param FileUrlProviderInterface $fileUrlProvider
     */
    public function setFileUrlProvider(FileUrlProviderInterface $fileUrlProvider): void
    {
        $this->fileUrlProvider = $fileUrlProvider;
    }

    /**
     * @param MimeTypeChecker $mimeTypeChecker
     */
    public function setMimeTypeChecker(MimeTypeChecker $mimeTypeChecker): void
    {
        $this->mimeTypeChecker = $mimeTypeChecker;
    }

    /**
     * @param File $file
     *
     * @return array
     */
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
