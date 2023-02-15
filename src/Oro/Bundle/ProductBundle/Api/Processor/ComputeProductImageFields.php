<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "types" and "files" field for Product Image entity.
 */
class ComputeProductImageFields implements ProcessorInterface
{
    private const TYPES_FIELD = 'types';
    private const FILES_FIELD = 'files';

    private AttachmentManager $attachmentManager;
    private ImageTypeProvider $typeProvider;

    public function __construct(AttachmentManager $attachmentManager, ImageTypeProvider $typeProvider)
    {
        $this->attachmentManager = $attachmentManager;
        $this->typeProvider = $typeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $isTypesFieldRequested = $context->isFieldRequestedForCollection(self::TYPES_FIELD, $data);
        $isFilesFieldRequested = $context->isFieldRequestedForCollection(self::FILES_FIELD, $data);
        if (!$isTypesFieldRequested && !$isFilesFieldRequested) {
            return;
        }

        $entityTypesFieldName = $context->getResultFieldName(self::TYPES_FIELD);
        $isWebpEnabled = $this->attachmentManager->isWebpEnabledIfSupported();

        foreach ($data as $key => $item) {
            $types = [];
            foreach ($item[$entityTypesFieldName] as $type) {
                $types[] = $type['type'];
            }

            if ($isTypesFieldRequested) {
                $data[$key][self::TYPES_FIELD] = $types;
            }
            if ($isFilesFieldRequested) {
                $image = $item['image'];
                $data[$key][self::FILES_FIELD] = $this->getImageUrls(
                    $image['id'],
                    $image['filename'],
                    $types,
                    $isWebpEnabled
                );
            }
        }

        $context->setData($data);
    }

    private function getImageUrls(int $imageId, string $fileName, array $imageTypes, bool $isWebpEnabled): array
    {
        if (empty($imageTypes)) {
            return [];
        }

        $allTypes = $this->typeProvider->getImageTypes();

        $result = [];
        foreach ($imageTypes as $imageType) {
            $typeDimensions = $allTypes[$imageType]->getDimensions();
            foreach ($typeDimensions as $dimensionName => $dimensionConfig) {
                if (!\array_key_exists($dimensionName, $result)) {
                    $result[$dimensionName] = $this->getImageUrl(
                        $imageId,
                        $fileName,
                        $dimensionName,
                        $dimensionConfig,
                        $isWebpEnabled
                    );
                }
                $result[$dimensionName]['types'][] = $imageType;
            }
        }

        return array_values($result);
    }

    private function getImageUrl(
        int $imageId,
        string $fileName,
        string $dimensionName,
        ThemeImageTypeDimension $dimensionConfig,
        bool $isWebpEnabled
    ): array {
        $result = [
            'url'       => $this->attachmentManager->getFilteredImageUrlByIdAndFilename(
                $imageId,
                $fileName,
                $dimensionName
            ),
            'maxWidth'  => $dimensionConfig->getWidth(),
            'maxHeight' => $dimensionConfig->getHeight(),
            'dimension' => $dimensionName
        ];
        if ($isWebpEnabled) {
            $result['url_webp'] = $this->attachmentManager->getFilteredImageUrlByIdAndFilename(
                $imageId,
                $fileName,
                $dimensionName,
                'webp'
            );
        }

        return $result;
    }
}
