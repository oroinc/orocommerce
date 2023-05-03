<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityFieldFilteringHelper;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

/**
 * Configures WYSIWYG fields in the following way:
 * * each WYSIWYG field converts to a computed string field. Its value is computed by
 *   {@see \Oro\Bundle\CMSBundle\Api\Processor\ComputeWYSIWYGFields}
 * * if API resource is not read-only, for each WYSIWYG field adds a nested object field with suffix "Raw"
 *   and with the following properties: "value", "style" and "properties"
 * * exclude source WYSIWYG fields
 */
class ConfigureSeparateWYSIWYGFields extends ConfigureWYSIWYGFields
{
    private const RAW_FIELD_SUFFIX = 'Raw';

    private ResourcesProvider $resourcesProvider;

    public function __construct(
        WYSIWYGFieldsProvider $wysiwygFieldsProvider,
        EntityFieldFilteringHelper $entityFieldFilteringHelper,
        DoctrineHelper $doctrineHelper,
        ResourcesProvider $resourcesProvider
    ) {
        parent::__construct($wysiwygFieldsProvider, $entityFieldFilteringHelper, $doctrineHelper);
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureWysiwygField(
        ConfigContext $context,
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $fieldName,
        bool $excluded
    ): void {
        $renderedWysiwygFieldName = $definition->findFieldNameByPropertyPath($fieldName) ?? $fieldName;
        $rawWysiwygFieldName = $renderedWysiwygFieldName . self::RAW_FIELD_SUFFIX;
        $isRenderedWysiwygFieldProcessed = self::isRenderedWysiwygFieldProcessed(
            $definition,
            $renderedWysiwygFieldName
        );
        $isRawWysiwygFieldProcessed = self::isWysiwygFieldProcessed(
            $definition,
            $rawWysiwygFieldName
        );

        if (!$isRawWysiwygFieldProcessed && !$this->isEntityField($entityClass, $rawWysiwygFieldName)) {
            $rawWysiwygField = $this->createWysiwygField(
                $definition,
                $rawWysiwygFieldName,
                $excluded || $this->isReadOnlyResource($context, $entityClass),
                $fieldName
            );
            $this->addNestedValueField($rawWysiwygField, $fieldName);
            $this->addNestedStyleField($rawWysiwygField, $entityClass, $fieldName);
            $this->addNestedPropertiesField($rawWysiwygField, $entityClass, $fieldName);
            self::registerWysiwygField($definition, $rawWysiwygFieldName);
        }

        if (!$isRenderedWysiwygFieldProcessed) {
            $styleFieldName = $this->getWysiwygStyleFieldName($entityClass, $fieldName);

            $renderedWysiwygField = $definition->getOrAddField($renderedWysiwygFieldName);
            $renderedWysiwygField->setDataType(DataType::STRING);
            $renderedWysiwygField->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
            if ($excluded && !$renderedWysiwygField->hasExcluded()) {
                $renderedWysiwygField->setExcluded();
            }
            $renderedWysiwygField->addDependsOn($fieldName);
            $renderedWysiwygField->addDependsOn($styleFieldName);

            self::registerRenderedWysiwygField($definition, $renderedWysiwygFieldName, $fieldName, $styleFieldName);
        }

        if (!$isRawWysiwygFieldProcessed || !$isRenderedWysiwygFieldProcessed) {
            $this->configureSourceWysiwygFields($definition, $entityClass, $fieldName);
        }
    }

    private function isReadOnlyResource(ConfigContext $context, string $entityClass): bool
    {
        return $this->resourcesProvider->isReadOnlyResource(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType()
        );
    }

    private function isEntityField(string $entityClass, string $fieldName): bool
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        return $metadata->hasField($fieldName) || $metadata->hasAssociation($fieldName);
    }
}
