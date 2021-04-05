<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Frontend\DataConverter;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Event\ProductExportDataConverterEvent;

/**
 * Prepare product data for export.
 */
class ProductExportDataConverter extends PropertyPathTitleDataConverter
{
    public const PRODUCT_NAME_FIELD = 'name';

    protected ConfigProvider $attributeConfigProvider;

    /**
     * @param ConfigProvider $attributeConfigProvider
     */
    public function setAttributeConfigProvider(ConfigProvider $attributeConfigProvider)
    {
        $this->attributeConfigProvider = $attributeConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function isFieldAvailableForExport(string $entityName, string $fieldName): bool
    {
        if (!is_a($entityName, Product::class, true)) {
            return parent::isFieldAvailableForExport($entityName, $fieldName);
        }

        $config = $this->attributeConfigProvider->getConfig($entityName, $fieldName);
        return $config->has('use_in_export') ? $config->get('use_in_export', false, false) : false;
    }

    /**
     * @param array $headersAndRules
     * @return array
     */
    protected function getHeadersAndRulesForCustomAttributes(array $headersAndRules): array
    {
        [$rules, $backendHeaders] = $headersAndRules;

        $rules = array_merge([self::PRODUCT_NAME_FIELD => self::PRODUCT_NAME_FIELD], $rules);
        $backendHeaders[] = self::PRODUCT_NAME_FIELD;

        if ($this->dispatcher) {
            $event = new ProductExportDataConverterEvent($rules, $backendHeaders);
            $this->dispatcher->dispatch(
                $event,
                ProductExportDataConverterEvent::FRONTEND_PRODUCT_CONVERT_TO_EXPORT_DATA
            );
            $rules = $event->getHeaderRules();
            $backendHeaders = $event->getBackendHeaders();
        }

        return [$backendHeaders, $rules];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityRulesAndBackendHeaders(
        $entityName,
        $fullData = false,
        $singleRelationDeepLevel = 0,
        $multipleRelationDeepLevel = 0
    ) {
        $headersAndRules = parent::getEntityRulesAndBackendHeaders(
            $entityName,
            $fullData,
            $singleRelationDeepLevel,
            $multipleRelationDeepLevel
        );

        if (is_a($entityName, Product::class, true)) {
            $headersAndRules = $this->getHeadersAndRulesForCustomAttributes($headersAndRules);
        }

        return $headersAndRules;
    }
}
