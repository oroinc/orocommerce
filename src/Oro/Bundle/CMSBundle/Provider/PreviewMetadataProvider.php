<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProviderInterface;
use Symfony\Component\Mime\MimeTypesInterface;

/**
 * Decorates preview metadata provider by adding image/file url for wysiwyg.
 */
class PreviewMetadataProvider implements PreviewMetadataProviderInterface
{
    private PreviewMetadataProviderInterface $innerPreviewMetadataProvider;

    private FileUrlProviderInterface $fileUrlProvider;

    private MimeTypeChecker $mimeTypeChecker;

    private MimeTypesInterface $mimeTypes;

    public function __construct(
        PreviewMetadataProviderInterface $innerPreviewMetadataProvider,
        FileUrlProviderInterface $fileUrlProvider,
        MimeTypeChecker $mimeTypeChecker,
        MimeTypesInterface $mimeTypes
    ) {
        $this->innerPreviewMetadataProvider = $innerPreviewMetadataProvider;
        $this->fileUrlProvider = $fileUrlProvider;
        $this->mimeTypeChecker = $mimeTypeChecker;
        $this->mimeTypes = $mimeTypes;
    }

    public function getMetadata(File $file): array
    {
        $metadata = $this->innerPreviewMetadataProvider->getMetadata($file);

        if ($this->mimeTypeChecker->isImageMimeType((string)$file->getMimeType())) {
            $format = $this->mimeTypes->getExtensions($file->getMimeType())[0] ?? '';
            $metadata['url'] = $this->fileUrlProvider->getFilteredImageUrl(
                $file,
                'wysiwyg_original',
                $format
            );
        } else {
            $metadata['url'] = $this->fileUrlProvider
                ->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD);
        }

        return $metadata;
    }
}
