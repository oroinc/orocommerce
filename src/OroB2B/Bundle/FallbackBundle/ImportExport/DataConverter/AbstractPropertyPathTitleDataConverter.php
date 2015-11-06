<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

abstract class AbstractPropertyPathTitleDataConverter extends AbstractTableDataConverter
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var FieldHelper */
    protected $fieldHelper;

    /**
     * @param ManagerRegistry $registry
     * @param FieldHelper $fieldHelper
     */
    public function __construct(ManagerRegistry $registry, FieldHelper $fieldHelper)
    {
        $this->registry = $registry;
        $this->fieldHelper = $fieldHelper;
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
        $entityName = $this->getEntityClass();

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

    /** {@inheritdoc} */
    protected function getBackendHeader()
    {
        $rules = $this->getHeaderConversionRules();
        $headers = reset($rules);

        return array_keys($headers);
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
            // @todo: relation calculator
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

    /** @return string */
    abstract protected function getEntityClass();
}
