<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "filePath" field for File entity if it is an image type.
 */
class ComputeImageFilePath implements ProcessorInterface
{
    private AttachmentManager $attachmentManager;
    private DoctrineHelper $doctrineHelper;
    private ImageTypeProvider $typeProvider;

    public function __construct(
        AttachmentManager $attachmentManager,
        DoctrineHelper $doctrineHelper,
        ImageTypeProvider $typeProvider
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->typeProvider = $typeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $filePathFieldName = $context->getResultFieldName('filePath');
        if (!$context->isFieldRequested($filePathFieldName, $data)) {
            return;
        }

        $mimeTypeFieldName = $context->getResultFieldName('mimeType');
        if (!$mimeTypeFieldName || empty($data[$mimeTypeFieldName])) {
            return;
        }

        $fileIdFieldName = $context->getResultFieldName('id');
        if (!$fileIdFieldName || empty($data[$fileIdFieldName])) {
            return;
        }

        $fileNameFieldName = $context->getResultFieldName('filename');
        if (!$fileNameFieldName || empty($data[$fileNameFieldName])) {
            return;
        }

        $filePaths = $this->getFilePaths(
            $data[$mimeTypeFieldName],
            $data[$fileIdFieldName],
            $data[$fileNameFieldName]
        );
        if (null !== $filePaths) {
            $data[$filePathFieldName] = $filePaths;
            $context->setData($data);
        }
    }

    private function getFilePaths(string $mimeType, int $fileId, string $fileName): ?array
    {
        if (!$this->attachmentManager->isImageType($mimeType)) {
            return null;
        }

        $imageTypes = $this->getImageTypes($fileId);
        if (!$imageTypes) {
            return null;
        }

        $allTypes = $this->typeProvider->getImageTypes();
        $isWebpEnabled = $this->attachmentManager->isWebpEnabledIfSupported();

        $result = [];
        foreach ($imageTypes as $imageType) {
            $typeDimensions = $allTypes[$imageType]->getDimensions();
            foreach ($typeDimensions as $dimensionName => $dimensionConfig) {
                if (!\array_key_exists($dimensionName, $result)) {
                    $result[$dimensionName] = $this->getFilePath(
                        $fileId,
                        $fileName,
                        $dimensionName,
                        $isWebpEnabled
                    );
                }
            }
        }

        return array_values($result);
    }

    private function getFilePath(
        int $fileId,
        string $fileName,
        string $dimensionName,
        bool $isWebpEnabled
    ): array {
        $result = [
            'url'       => $this->attachmentManager->getFilteredImageUrlByIdAndFilename(
                $fileId,
                $fileName,
                $dimensionName
            ),
            'dimension' => $dimensionName
        ];
        if ($isWebpEnabled) {
            $result['url_webp'] = $this->attachmentManager->getFilteredImageUrlByIdAndFilename(
                $fileId,
                $fileName,
                $dimensionName,
                'webp'
            );
        }

        return $result;
    }

    private function getImageTypes(int $fileId): array
    {
        $imageTypes = $this->doctrineHelper
            ->createQueryBuilder(ProductImageType::class, 'imageType')
            ->select('imageType.type')
            ->innerJoin('imageType.productImage', 'image')
            ->where('image.image = :fileId')
            ->setParameter('fileId', $fileId)
            ->getQuery()
            ->getArrayResult();

        return array_column($imageTypes, 'type');
    }
}
