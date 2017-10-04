<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\Shared;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the file path(or paths) of a file if it's an image type to the File API endpoints
 */
class ProcessImagePaths implements ProcessorInterface
{
    const CONFIG_FILE_PATH = 'filePath';

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ImageTypeProvider
     */
    protected $typeProvider;

    /**
     * @param AttachmentManager $attachmentManager
     * @param DoctrineHelper $doctrineHelper
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
     */
    public function process(ContextInterface $context)
    {
        $result = $context->getResult();
        $filePathField = $context->getConfig()->getField(self::CONFIG_FILE_PATH);

        if (!is_array($result) || !$filePathField || $filePathField->isExcluded()) {
            return;
        }

        //update result with computed file path
        $result = $this->addPathToResult($result);

        $context->setResult($result);
    }

    /**
     * @param array $result
     * @return array
     */
    protected function addPathToResult(array $result)
    {
        if (!$this->attachmentManager->isImageType($result['mimeType'])) {
            return $result;
        }

        /** @var ProductImage $productImage */
        $productImage = $this->doctrineHelper->getEntityRepository(ProductImage::class)->findOneBy(
            ['image' => $result['id']]
        );

        if (!$productImage || empty($types = $productImage->getTypes())) {
            return $result;
        }

        $allTypes = $this->typeProvider->getImageTypes();

        $dimensions = [];
        foreach ($productImage->getTypes() as $imageType) {
            $dimensions = array_merge($dimensions, $allTypes[$imageType->getType()]->getDimensions());
        }

        $urls = [];
        foreach (array_keys($dimensions) as $dimension) {
            $urls[$dimension] = $this->attachmentManager->getFilteredImageUrl(
                $productImage->getImage(),
                $dimension
            );
        }
        $result[self::CONFIG_FILE_PATH] = $urls;

        return $result;
    }
}
