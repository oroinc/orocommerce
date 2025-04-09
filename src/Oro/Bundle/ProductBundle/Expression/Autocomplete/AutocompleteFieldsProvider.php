<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

/**
 * Data provider for fields autocomplete, used in ExpressionEditors
 */
class AutocompleteFieldsProvider extends AbstractAutocompleteFieldsProvider
{
    #[\Override]
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
