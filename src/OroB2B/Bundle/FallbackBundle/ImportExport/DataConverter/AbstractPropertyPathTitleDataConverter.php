<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

abstract class AbstractPropertyPathTitleDataConverter extends AbstractTableDataConverter
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var FieldHelper */
    protected $fieldHelper;

    /**
     * @var RelationCalculator
     */
    protected $relationCalculator;

    /** @var string */
    protected $delimiter = '.';

    /**
     * @param ManagerRegistry $registry
     * @param FieldHelper $fieldHelper
     * @param RelationCalculator $relationCalculator
     */
    public function __construct(
        ManagerRegistry $registry,
        FieldHelper $fieldHelper,
        RelationCalculator $relationCalculator
    ) {
        $this->registry = $registry;
        $this->fieldHelper = $fieldHelper;
        $this->relationCalculator = $relationCalculator;
    }

    /**
     * {@inheritdoc}
     *
     * @todo: ordering support
     */
    protected function getHeaderConversionRules()
    {
        $headerConversionRules = [];
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
                $this->processRelation($headerConversionRules, $fieldHeader, $field, $entityName);

                continue;
            }

            $this->processScalarField($headerConversionRules, $fieldHeader);
        }

        return $headerConversionRules;
    }

    /** {@inheritdoc} */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }

    /**
     * @param array $conversionRules
     * @param string $fieldHeader
     */
    protected function processScalarField(array &$conversionRules, $fieldHeader)
    {
        $conversionRules[$fieldHeader] = $fieldHeader;
    }

    /**
     * @param array $conversionRules
     * @param string $fieldHeader
     * @param string $field
     * @param string $entityName
     */
    protected function processRelation(array &$conversionRules, $fieldHeader, $field, $entityName)
    {
        $fieldName = $field['name'];
        if ($this->fieldHelper->isSingleRelation($field)) {
            $conversionRules[$fieldHeader . $this->delimiter . $fieldName] =
                $fieldHeader . $this->convertDelimiter . $fieldName;
        }

        if ($this->fieldHelper->isMultipleRelation($field)) {
            $maxEntities = $this->relationCalculator->getMaxRelatedEntities($entityName, $fieldName);
            for ($i = 0; $i < $maxEntities; $i++) {
                $frontendHeader = $fieldHeader . $this->delimiter . $i . $this->delimiter . $fieldName;
                $backendRule = $fieldHeader . $this->convertDelimiter . $i . $this->convertDelimiter . $fieldName;
                $conversionRules[$frontendHeader] = $backendRule;
            }
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
