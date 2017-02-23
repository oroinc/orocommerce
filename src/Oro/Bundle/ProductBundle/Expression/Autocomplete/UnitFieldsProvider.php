<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

class UnitFieldsProvider extends AbstractAutocompleteFieldsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldsData($numericalOnly, $withRelations)
    {
        if ($numericalOnly) {
            return [];
        }

        $result = [];
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
                if ($isRelation) {
                    $relatedEntityName = $fieldInfo['related_entity_name'];
                    $isUnit = is_a($relatedEntityName, MeasureUnitInterface::class, true);

                    if ($isUnit || $withRelations) {
                        $result[$className][$fieldName] = [
                            'label' => $fieldInfo['label']
                        ];

                        if ($isUnit) {
                            $result[$className][$fieldName]['type'] = self::TYPE_STRING;
                        } else {
                            $result[$className][$fieldName]['relation_alias'] = $relatedEntityName;
                            $result[$className][$fieldName]['type'] = $type;
                            $this->fillFields($result, $relatedEntityName, true);
                        }
                    }
                }
            }
        }
    }
}
