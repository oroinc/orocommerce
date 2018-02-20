<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the file paths to the File entity if it is an image type.
 */
class ProcessImagePaths implements ProcessorInterface
{
    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ImageTypeProvider */
    private $typeProvider;

    /**
     * @param AttachmentManager $attachmentManager
     * @param DoctrineHelper    $doctrineHelper
     * @param ImageTypeProvider $typeProvider
     */
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data)) {
            return;
        }

        $config = $context->getConfig();

        $filePathFieldName = $config->findFieldNameByPropertyPath('filePath');
        if (!$filePathFieldName
            || $config->getField($filePathFieldName)->isExcluded()
            || array_key_exists($filePathFieldName, $data)
        ) {
            // the file path field is undefined, excluded or already added
            return;
        }

        $mimeTypeFieldName = $config->findFieldNameByPropertyPath('mimeType');
        if (!$mimeTypeFieldName || empty($data[$mimeTypeFieldName])) {
            // the mime type field is undefined or its value is unknown
            return;
        }

        $fileIdFieldName = $config->findFieldNameByPropertyPath('id');
        if (!$fileIdFieldName || empty($data[$fileIdFieldName])) {
            // the file id field is undefined or its value is unknown
            return;
        }

        $filePaths = $this->getFilePaths($data[$mimeTypeFieldName], $data[$fileIdFieldName]);
        if (null !== $filePaths) {
            $data[$filePathFieldName] = $filePaths;
            $context->setResult($data);
        }
    }

    /**
     * @param string $mimeType
     * @param int    $fileId
     *
     * @return string[]|null [dimension => path, ...]
     */
    private function getFilePaths($mimeType, $fileId)
    {
        if (!$this->attachmentManager->isImageType($mimeType)) {
            return null;
        }

        /** @var ProductImage $productImage */
        $productImage = $this->doctrineHelper->getEntityRepository(ProductImage::class)
            ->findOneBy(['image' => $fileId]);
        if (null === $productImage) {
            return null;
        }
        $imageTypes = $productImage->getTypes();
        if (empty($imageTypes)) {
            return null;
        }

        $allTypes = $this->typeProvider->getImageTypes();

        $dimensions = [];
        foreach ($imageTypes as $imageType) {
            $dimensions = array_merge($dimensions, $allTypes[$imageType->getType()]->getDimensions());
        }

        $result = [];
        foreach (array_keys($dimensions) as $dimension) {
            $result[$dimension] = $this->attachmentManager->getFilteredImageUrl(
                $productImage->getImage(),
                $dimension
            );
        }

        return $result;
    }
}
