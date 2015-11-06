<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

class LocalizedFallbackValueAwareStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var string */
    protected $localizedFallbackValueClass;

    /**
     * @param string $localizedFallbackValueClass
     */
    public function setLocalizedFallbackValueClass($localizedFallbackValueClass)
    {
        $this->localizedFallbackValueClass = $localizedFallbackValueClass;
    }

    /** {@inheritdoc} */
    protected function beforeProcessEntity($entity)
    {
        $existingEntity = $this->findExistingEntity($entity);

        $fields = $this->fieldHelper->getFields(ClassUtils::getClass($existingEntity), true);
        foreach ($fields as $field) {
            if ($this->fieldHelper->isRelation($field)) {
                $targetClassName = $field['related_entity_name'];
                if (is_a($targetClassName, $this->localizedFallbackValueClass, true)) {
                    $fieldName = $field['name'];
                    $this->mapCollections(
                        $this->fieldHelper->getObjectValue($entity, $fieldName),
                        $this->fieldHelper->getObjectValue($existingEntity, $fieldName)
                    );
                }
            }
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * @param Collection $importedCollection
     * @param Collection $sourceCollection
     */
    protected function mapCollections(Collection $importedCollection, Collection $sourceCollection)
    {
        $importedCollection
            ->map(
                function (LocalizedFallbackValue $importedValue) use ($sourceCollection) {
                    $sourceValues = $sourceCollection
                        ->filter(
                            function (LocalizedFallbackValue $sourceValue) use ($importedValue) {
                                if ($sourceValue->getLocale() === $importedValue->getLocale()) {
                                    return true;
                                }

                                return $sourceValue->getLocale()
                                    && $importedValue->getLocale()
                                    && $sourceValue->getLocale()->getCode() === $importedValue->getLocale()->getCode();
                            }
                        );

                    if (!$sourceValues->isEmpty()) {
                        /** @var LocalizedFallbackValue $sourceValue */
                        $sourceValue = $sourceValues->first();

                        $this->fieldHelper->setObjectValue($importedValue, 'id', $sourceValue->getId());
                    }
                }
            );
    }

    /**
     * {@inheritdoc}
     *
     * No need to search LocalizedFallbackValue by identity fields, consider entities without ids as new
     */
    protected function findEntityByIdentityValues($entityName, array $identityValues)
    {
        if (is_a($entityName, $this->localizedFallbackValueClass, true)) {
            return null;
        }

        return parent::findEntityByIdentityValues($entityName, $identityValues);
    }
}
