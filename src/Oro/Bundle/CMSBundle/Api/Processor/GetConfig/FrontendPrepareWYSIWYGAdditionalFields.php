<?php

namespace Oro\Bundle\CMSBundle\Api\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Exclude WYSIWYG additional fields from API result
 */
class FrontendPrepareWYSIWYGAdditionalFields implements ProcessorInterface
{
    /**
     * @var WYSIWYGFieldsProvider
     */
    private $wysiwygFieldsProvider;

    /**
     * @param WYSIWYGFieldsProvider $wysiwygFieldsProvider
     */
    public function __construct(WYSIWYGFieldsProvider $wysiwygFieldsProvider)
    {
        $this->wysiwygFieldsProvider = $wysiwygFieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */
        $definition = $context->getResult();
        $entityClass = $context->getClassName();

        $wysiwygAttributes = $this->wysiwygFieldsProvider->getWysiwygAttributes($entityClass);
        if (empty($wysiwygAttributes)) {
            return;
        }

        $this->processWysiwygFields($definition, $wysiwygAttributes);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param array $wysiwygAttributes
     */
    protected function processWysiwygFields(EntityDefinitionConfig $definition, array $wysiwygAttributes): void
    {
        foreach ($wysiwygAttributes as $fieldName) {
            if ($definition->hasField($fieldName)) {
                $wysiwygField = $definition->getField($fieldName);

                if ($definition->hasField('productAttributes')) {
                    $attributesField = $definition->getField('productAttributes');
                    if (!$attributesField->getDependsOn()) {
                        $dependsOn = [$fieldName];
                    } else {
                        $dependsOn = array_merge(($attributesField->getDependsOn()), [$fieldName]);
                    }

                    $attributesField->setDependsOn($dependsOn);
                    $wysiwygField->setExcluded();
                }
            }
        }
    }
}
