<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

abstract class LocalizedFallbackValueAwareStrategy extends ConfigurableAddOrReplaceStrategy
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
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        if (is_a($entity, $this->localizedFallbackValueClass, true)) {
            return $this->findLocalizedFallbackValue($entity, $searchContext);
        }

        return parent::findExistingEntity($entity, $searchContext);
    }

    /**
     * @param object $entity
     * @param array $searchContext
     * @return LocalizedFallbackValue|null
     */
    protected function findLocalizedFallbackValue($entity, array $searchContext = [])
    {
        $holder = $this->getLocalizedFallbackValueHolder();
        if (!$holder) {
            return;
        }

        return;
    }

    /** @return object|null */
    abstract protected function getLocalizedFallbackValueHolder();
}
