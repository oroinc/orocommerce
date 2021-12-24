<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Extends a value of "files" field for Product Image entity with URL to resized image in WebP format.
 */
class ComputeWebpAwareProductImageFields implements ProcessorInterface
{
    private const FILES_FIELD  = 'files';

    private AttachmentManager $attachmentManager;

    public function __construct(AttachmentManager $attachmentManager)
    {
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * {@inheritdoc}
     * @param CustomizeLoadedDataContext $context
     */
    public function process(ContextInterface $context)
    {
        if (!$this->attachmentManager->isWebpEnabledIfSupported()) {
            return;
        }

        $data = $context->getData();
        $filesFieldName = $context->getResultFieldName(self::FILES_FIELD);

        foreach ($data as $key => $item) {
            if (isset($item[$filesFieldName], $item['image']) && $item[$filesFieldName]) {
                $image = $item['image'];
                $data[$key][$filesFieldName] = $this->getImageUrls(
                    $image['id'],
                    $image['filename'],
                    $item[$filesFieldName]
                );
            }
        }

        $context->setData($data);
    }

    private function getImageUrls(int $imageId, string $fileName, array $files): array
    {
        foreach ($files as $key => $file) {
            $files[$key]['url_webp'] = $this->attachmentManager
                ->getFilteredImageUrlByIdAndFilename(
                    $imageId,
                    $fileName,
                    $file['dimension'],
                    'webp'
                );
        }

        return $files;
    }
}
