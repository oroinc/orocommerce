<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the file path(or paths) of a file if it's an image type to the File API endpoints
 */
class ProcessImagePathsConfig implements ProcessorInterface
{
    const CONFIG_FILE_PATH = 'filePath';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var EntityDefinitionConfig $config */
        $config = $context->getResult();
        if ($config->hasField(self::CONFIG_FILE_PATH)) {
            return;
        }

        // add the filePath as a field config
        $fieldConfig = new EntityDefinitionFieldConfig();
        $fieldConfig->set('data_type', 'array');
        $fieldConfig->setFormOptions(
            [
                'mapped' => false
            ]
        );

        $config->addField(self::CONFIG_FILE_PATH, $fieldConfig);
    }
}
