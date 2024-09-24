<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

/**
 * Provides information about currency fields.
 */
class CurrencyFieldsProvider extends AbstractAutocompleteFieldsProvider
{
    #[\Override]
    protected function getFieldsData($numericalOnly, $withRelations)
    {
        $result = [];
        if ($numericalOnly) {
            return [];
        }

        foreach ($this->expressionParser->getNamesMapping() as $rootEntityClassName) {
            $this->fillFields($result, $rootEntityClassName, $withRelations);
        }

        $this->removeEmptyRelations($result);

        return $result;
    }

    /**
     * @param array $result
     * @param string $className
     * @param bool $withRelations
     */
    protected function fillFields(array &$result, $className, $withRelations)
    {
        if (!array_key_exists($className, $result)) {
            $fields = $this->fieldsProvider->getDetailedFieldsInformation($className, false, $withRelations);
            foreach ($fields as $fieldName => $fieldInfo) {
                $type = $this->getMappedType($fieldInfo['type']);
                if (!$type) {
                    continue;
                }

                $isRelation = $type === self::TYPE_RELATION;
                if ($isRelation || (str_contains($fieldName, 'currency') && self::TYPE_STRING === $type)) {
                    $result[$className][$fieldName] = [
                        'label' => $fieldInfo['label'],
                        'type' => $type
                    ];
                }
                if ($isRelation) {
                    $relatedEntityName = $fieldInfo['related_entity_name'];
                    $result[$className][$fieldName]['relation_alias'] = $relatedEntityName;
                    $this->fillFields($result, $relatedEntityName, false);
                }
            }
        }
    }

    #[\Override]
    public function getDataProviderConfig($numericalOnly = false, $withRelations = true)
    {
        $whitelist = [];

        $entitiesData = $this->getFieldsData($numericalOnly, $withRelations);
        foreach ($entitiesData as $className => $fieldsData) {
            foreach ($fieldsData as $fieldName => $fieldInfo) {
                $whitelist[$className][$fieldName] = true;
            }
        }

        $dataProviderConfig = [
            'fieldsFilterWhitelist' => $whitelist,
            'isRestrictiveWhitelist' => true,
        ];

        if (!empty($this->specialFieldsInformation)) {
            $dataProviderConfig['fieldsDataUpdate'] = $this->translateLabels($this->specialFieldsInformation);
        }

        return $dataProviderConfig;
    }
}
