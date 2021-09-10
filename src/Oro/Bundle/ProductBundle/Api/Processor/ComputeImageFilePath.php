<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "filePath" field for File entity if it is an image type.
 */
class ComputeImageFilePath implements ProcessorInterface
{
    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ImageTypeProvider */
    private $typeProvider;

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

        $filePaths = $this->getFilePaths($data[$mimeTypeFieldName], $data[$fileIdFieldName]);
        if (null !== $filePaths) {
            $data[$filePathFieldName] = $filePaths;
            $context->setData($data);
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
