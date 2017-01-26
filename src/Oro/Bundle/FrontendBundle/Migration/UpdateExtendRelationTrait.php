<?php

namespace Oro\Bundle\FrontendBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

trait UpdateExtendRelationTrait
{
    /**
     * @param ConfigManager $configManager
     * @param string $entityFrom
     * @param string $entityTo
     * @param string $relationFrom
     * @param string $relationTo
     * @param string $relationType
     */
    public function migrateConfig(
        ConfigManager $configManager,
        $entityFrom,
        $entityTo,
        $relationFrom,
        $relationTo,
        $relationType
    ) {
        $configManager->clearCache();

        $entityConfigModel = $configManager->getConfigEntityModel($entityFrom);
        if (!$entityConfigModel) {
            return;
        }
        $data = $entityConfigModel->toArray('extend');

        $fullRelationFrom = implode(
            '|',
            [$relationType, $entityFrom, $entityTo, $relationFrom]
        );
        $fullRelationTo = implode(
            '|',
            [$relationType, $entityFrom, $entityTo, $relationTo]
        );
        if (array_key_exists($fullRelationFrom, $data['relation'])) {
            $data['relation'][$fullRelationTo] =
                $data['relation'][$fullRelationFrom];
            unset($data['relation'][$fullRelationFrom]);

            if (isset($data['relation'][$fullRelationTo]['field_id'])) {
                /** @var FieldConfigId $fieldId */
                $fieldId = $data['relation'][$fullRelationTo]['field_id'];
                $reflectionProperty = new \ReflectionProperty(get_class($fieldId), 'fieldName');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($fieldId, $relationTo);
                $data['relation'][$fullRelationTo]['field_id'] = $fieldId;
            }
        }

        if (array_key_exists($relationFrom, $data['schema']['relation'])) {
            $data['schema']['relation'][$relationTo] =
                $data['schema']['relation'][$relationFrom];
            unset($data['schema']['relation'][$relationFrom]);
        }
        if (array_key_exists($relationFrom, $data['schema']['addremove'])) {
            $data['schema']['addremove'][$relationTo] =
                $data['schema']['addremove'][$relationFrom];
            unset($data['schema']['addremove'][$relationFrom]);
        }

        $entityConfigModel->fromArray('extend', $data, []);
        $configManager->updateConfigEntityModel($entityFrom, true);

        $configManager->changeFieldName($entityFrom, $relationFrom, $relationTo);

        $configManager->flush();
        $configManager->clearCache();
    }
}
