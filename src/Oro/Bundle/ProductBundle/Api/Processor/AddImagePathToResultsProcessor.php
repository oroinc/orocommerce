<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Class AddImagePathToResultsProcessor
 *
 * Adds the file path(or paths) of a file if it's an image type to the File API endpoints
 */
class AddImagePathToResultsProcessor implements ProcessorInterface
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
        // include filePath into config, otherwise it will be ignored in the response
        $this->addFilePathToConfig($context);

        $results = $context->getResult();
        if (!is_array($results)) {
            return;
        }

        if (isset($results['content'])) {
            // single item request
            $results = $this->addPathToResults($results);
        } else {
            // collection request
            foreach ($results as &$fileItem) {
                $fileItem = $this->addPathToResults($fileItem);
            }
        }

        $context->setResult($results);
    }

    /**
     * @param ContextInterface $context
     */
    protected function addFilePathToConfig(ContextInterface $context)
    {
        /** @var EntityDefinitionConfig $config */
        $config = $context->getConfig();
        if ($config->hasField(self::CONFIG_FILE_PATH)) {
            return;
        }
        // add the filePath as a field config, otherwise it will be ignored
        $fieldConfig = new EntityDefinitionFieldConfig();
        $fieldConfig->set('data_type', 'string');
        $config->addField(self::CONFIG_FILE_PATH, $fieldConfig);
        // Set a new config key and set metadata to null to trigger a new metadata refresh, otherwise the new field won't
        // be populated in the response, as metadata is cached
        $config->setKey($config->getKey() . 'new');
        $context->setConfig($config);
        $context->setMetadata(null);
        $context->getMetadata();
    }

    /**
     * @param array $results
     * @return array
     */
    protected function addPathToResults(array $results)
    {
        if (!$this->attachmentManager->isImageType($results['mimeType'])) {
            return $results;
        }

        /** @var ProductImage $productImage */
        $productImage = $this->doctrineHelper->getEntityRepository(ProductImage::class)->findOneBy(
            ['image' => $results['id']]
        );

        if (!$productImage) {
            return $results;
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

        $results[self::CONFIG_FILE_PATH] = $urls;

        return $results;
    }
}
