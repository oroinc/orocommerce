<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter;

use Oro\Bundle\UIBundle\Tools\ArrayUtils;
use OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocaleCodeFormatter;
use OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository;

abstract class AbstractLocalizedFallbackValueAwareDataConverter extends AbstractPropertyPathTitleDataConverter
{
    const FIELD_VALUE = 'value';
    const FIELD_FALLBACK = 'fallback';

    /** @var string */
    protected $localeClassName;

    /** @var string */
    protected $localizedFallbackValueClassName;

    /** @var string[] */
    private $localeCodes;

    /**
     * @param string $localeClassName
     */
    public function setLocaleClassName($localeClassName)
    {
        $this->localeClassName = $localeClassName;
    }

    /**
     * @param string $localizedFallbackValueClassName
     */
    public function setLocalizedFallbackValueClassName($localizedFallbackValueClassName)
    {
        $this->localizedFallbackValueClassName = $localizedFallbackValueClassName;
    }

    /** @return string[] */
    protected function getLocaleCodes()
    {
        if (null === $this->localeCodes) {
            /** @var LocaleRepository $localeRepository */
            $localeRepository = $this->registry->getRepository($this->localeClassName);
            $this->localeCodes = ArrayUtils::arrayColumn($localeRepository->getLocaleCodes(), 'code');
        }

        return $this->localeCodes;
    }

    /**
     * @param array $conversionRules
     * @param string $fieldHeader
     * @param string $field
     * @param string $entityName
     */
    protected function processRelation(array &$conversionRules, $fieldHeader, $field, $entityName)
    {
        $targetClass = $field['related_entity_name'];

        if (is_a($targetClass, $this->localizedFallbackValueClassName, true)
            && $this->fieldHelper->isMultipleRelation($field)
        ) {
            $localeCodes = $this->getLocaleCodes();

            foreach ($localeCodes as $localeCode) {
                foreach ([self::FIELD_VALUE, self::FIELD_FALLBACK] as $exportField) {
                    $frontendHeader = $field['name']
                        . $this->delimiter . LocaleCodeFormatter::format($localeCode)
                        . $this->delimiter . $exportField;
                    $backendHeaders = $field['name'] . ':'
                        . LocaleCodeFormatter::format($localeCode)
                        . ':' . $exportField;
                    $conversionRules[$frontendHeader] = $backendHeaders;
                }
            }

            return;
        }

        parent::processRelation($conversionRules, $fieldHeader, $field, $entityName);
    }
}
