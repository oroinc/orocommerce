<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

/**
 * Data provider for fields autocomplete, used in ExpressionEditors
 */
class AutocompleteFieldsProvider extends AbstractAutocompleteFieldsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldsData($numericalOnly, $withRelations)
    {
        $result = [];
        foreach ($this->expressionParser->getNamesMapping() as $rootEntityClassName) {
            $this->fillFields($result, $rootEntityClassName, $numericalOnly, $withRelations);
        }

        if ($numericalOnly) {
            $this->removeEmptyRelations($result);
        }

        return $result;
    }

    /**
     * @param array $result
     * @param string $className
     * @param bool $numericalOnly
     * @param bool $withRelations
     */
    protected function fillFields(array &$result, $className, $numericalOnly, $withRelations)
    {
        if (!array_key_exists($className, $result)) {
            $fields = $this->fieldsProvider->getDetailedFieldsInformation($className, $numericalOnly, $withRelations);
            foreach ($fields as $fieldName => $fieldInfo) {
                $type = $this->getMappedType($fieldInfo['type']);
                if (!$type) {
                    continue;
                }
                if ($numericalOnly && $fieldName === 'id') {
                    continue;
                }
                $result[$className][$fieldName] = [
                    'label' => $fieldInfo['label'],
                    'type' => $type
                ];
                if ($type === self::TYPE_RELATION) {
                    $relatedEntityName = $fieldInfo['related_entity_name'];
                    $result[$className][$fieldName]['relation_alias'] = $relatedEntityName;
                    $this->fillFields($result, $relatedEntityName, $numericalOnly, false);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDataProviderConfig($numericalOnly = false, $withRelations = true)
    {
        $dataProviderConfig = parent::getDataProviderConfig($numericalOnly, $withRelations);
        $specialFields = $this->specialFieldsInformation;
        if ($numericalOnly) {
            $supportedTypes = $this->fieldsProvider->getSupportedNumericTypes();
            foreach ($specialFields as $className => $fieldList) {
                $specialFields[$className] = array_filter($fieldList, static function ($field) use ($supportedTypes) {
                    return \in_array($field['type'] ?? null, $supportedTypes, true);
                });
                if (empty($specialFields[$className])) {
                    unset($specialFields[$className]);
                }
            }
        }
        $dataProviderConfig['fieldsDataUpdate'] = $this->translateLabels($specialFields);

        return $dataProviderConfig;
    }
}
