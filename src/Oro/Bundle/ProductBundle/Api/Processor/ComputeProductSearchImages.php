<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "images" field for ProductSearch entity.
 */
class ComputeProductSearchImages implements ProcessorInterface
{
    private const IMAGES_FIELD = 'images';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequested(self::IMAGES_FIELD, $data)) {
            return;
        }

        $images = [];
        $mediumImageUrl = $data['text_image_product_medium'];
        if ($mediumImageUrl) {
            $images[] = $this->getImageInfo($mediumImageUrl, 'medium');
        }
        $largeImageUrl = $data['text_image_product_large'];
        if ($largeImageUrl) {
            $images[] = $this->getImageInfo($largeImageUrl, 'large');
        }

        $data[self::IMAGES_FIELD] = $images;

        $context->setData($data);
    }

    private function getImageInfo(string $imageUrl, string $imageType): array
    {
        return [
            'url'  => $imageUrl,
            'type' => $imageType
        ];
    }
}
