<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\DataConverter;

use OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\AbstractLocalizedFallbackValueAwareDataConverter;

class ProductDataConverter extends AbstractLocalizedFallbackValueAwareDataConverter
{
    /** @var string */
    protected $productClass;

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * @inheritDoc
     */
    protected function getHolderClassName()
    {
        return $this->productClass;
    }

    /**
     * {@inheritdoc}
     *
     * @todo: ordering support
     */
    protected function getHeaderConversionRules()
    {
        $rules = [];
        $backendHeaders = [];
        $entityName = $this->getHolderClassName();

        $fields = $this->fieldHelper->getFields($entityName, true);
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded')) {
                continue;
            }

            $fieldHeader = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'header', $fieldName);

            if ($this->fieldHelper->isRelation($field)
                && !$this->fieldHelper->processRelationAsScalar($entityName, $fieldName)
            ) {
                $this->processRelation($rules, $backendHeaders, $fieldHeader, $field);

                continue;
            }

            $this->processScalarField($rules, $backendHeaders, $fieldHeader);
        }

        return [$rules, $backendHeaders];
    }

    protected function processScalarField(&$rules, &$backendHeaders, $fieldHeader)
    {
        $rules[$fieldHeader] = ['value' => $fieldHeader, 'order' => false];
        $backendHeaders[] = $rules[$fieldHeader];
    }

    protected function processRelation(&$rules, &$backendHeaders, $fieldHeader, $field)
    {
        if ($this->fieldHelper->isSingleRelation($field)) {
            $rules[$fieldHeader] = ['value' => $fieldHeader, 'order' => false];
            $backendHeaders[] = $rules[$fieldHeader];
        }

        if ($this->fieldHelper->isMultipleRelation($field)) {
        }
    }

    /**
     * @param array $header
     * @param array $data
     * @return array
     */
    protected function fillEmptyColumns(array $header, array $data)
    {
        $result = [];
        foreach ($header as $headerKey) {
            $result[$headerKey] = array_key_exists($headerKey, $data) ? $data[$headerKey] : '';
        }

        return $result;
    }
}
