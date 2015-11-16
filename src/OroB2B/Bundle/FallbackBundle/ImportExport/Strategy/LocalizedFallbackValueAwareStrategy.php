<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocaleCodeFormatter;

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
        if (!$existingEntity) {
            return parent::beforeProcessEntity($entity);
        }

        $fields = $this->fieldHelper->getRelations(ClassUtils::getClass($existingEntity), true);
        foreach ($fields as $field) {
            $targetClassName = $field['related_entity_name'];
            if (is_a($targetClassName, $this->localizedFallbackValueClass, true)) {
                $fieldName = $field['name'];
                $this->mapCollections(
                    $this->fieldHelper->getObjectValue($entity, $fieldName),
                    $this->fieldHelper->getObjectValue($existingEntity, $fieldName)
                );
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
        if ($importedCollection->isEmpty()) {
            return;
        }

        if ($sourceCollection->isEmpty()) {
            return;
        }

        $sourceCollectionArray = $sourceCollection->toArray();

        /** @var LocalizedFallbackValue $sourceValue */
        foreach ($sourceCollectionArray as $sourceValue) {
            $sourceCollectionArray[LocaleCodeFormatter::formatKey($sourceValue->getLocale())] = $sourceValue->getId();
        }

        $importedCollection
            ->map(
                function (LocalizedFallbackValue $importedValue) use ($sourceCollectionArray) {
                    $key = LocaleCodeFormatter::formatKey($importedValue->getLocale());
                    if (array_key_exists($key, $sourceCollectionArray)) {
                        $this->fieldHelper->setObjectValue($importedValue, 'id', $sourceCollectionArray[$key]);
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
