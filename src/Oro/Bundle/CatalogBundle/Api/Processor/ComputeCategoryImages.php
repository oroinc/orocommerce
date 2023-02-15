<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "images" field for Category entity.
 */
class ComputeCategoryImages implements ProcessorInterface
{
    private const IMAGES_FIELD = 'images';

    private AttachmentManager $attachmentManager;

    public function __construct(AttachmentManager $attachmentManager)
    {
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequestedForCollection(self::IMAGES_FIELD, $data)) {
            return;
        }

        $smallImageFieldName = $context->getResultFieldName('smallImage');
        $largeImageFieldName = $context->getResultFieldName('largeImage');

        foreach ($data as $key => $item) {
            $images = [];

            $smallImage = $item[$smallImageFieldName];
            if ($smallImage) {
                $images[] = $this->getImageInfo($smallImage, 'category_medium', 'small');
            }

            $largeImage = $item[$largeImageFieldName];
            if ($largeImage) {
                $images[] = $this->getImageInfo($largeImage, 'product_original', 'large');
            }

            $data[$key][self::IMAGES_FIELD] = $images;
        }

        $context->setData($data);
    }

    private function getImageInfo(array $image, string $imageFilter, string $imageType): array
    {
        return [
            'mimeType' => $image['mimeType'],
            'url'      => $this->attachmentManager->getFilteredImageUrlByIdAndFilename(
                $image['id'],
                $image['filename'],
                $imageFilter
            ),
            'type'     => $imageType
        ];
    }
}
