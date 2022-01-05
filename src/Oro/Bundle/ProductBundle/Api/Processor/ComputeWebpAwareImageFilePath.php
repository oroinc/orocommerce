<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Extends a value of "filePath" field for File entity with URL to resized image in WebP format if it is an image type.
 */
class ComputeWebpAwareImageFilePath implements ProcessorInterface
{
    private AttachmentManager $attachmentManager;

    private DoctrineHelper $doctrineHelper;

    public function __construct(AttachmentManager $attachmentManager, DoctrineHelper $doctrineHelper)
    {
        $this->attachmentManager = $attachmentManager;
        $this->doctrineHelper = $doctrineHelper;
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

        $filePathFieldName = $context->getResultFieldName('filePath');
        $filePaths = $data[$filePathFieldName] ?? [];

        if ($filePaths === []) {
            return;
        }

        $fileIdFieldName = $context->getResultFieldName('id');
        if (!$fileIdFieldName || empty($data[$fileIdFieldName])) {
            return;
        }

        /** @var ProductImage $productImage */
        $productImage = $this->doctrineHelper->getEntityRepository(ProductImage::class)
            ->findOneBy(['image' => $data[$fileIdFieldName]]);
        if (null === $productImage) {
            return;
        }

        foreach ($filePaths as $key => $item) {
            if (isset($item['dimension'])) {
                $data[$filePathFieldName][$key]['url_webp'] = $this->attachmentManager->getFilteredImageUrl(
                    $productImage->getImage(),
                    $item['dimension'],
                    'webp'
                );
            }
        }

        $context->setData($data);
    }
}
