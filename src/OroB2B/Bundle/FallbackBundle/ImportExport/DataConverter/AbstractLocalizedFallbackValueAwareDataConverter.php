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
            $this->localeCodes = [LocaleCodeFormatter::DEFAULT_LOCALE] +
                ArrayUtils::arrayColumn($localeRepository->getLocaleCodes(), 'code');
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
            $targetField = $this->fieldHelper->getConfigValue($entityName, $field['name'], 'fallback_field', 'string');
            $fieldName = $field['name'];

            foreach ($localeCodes as $localeCode) {
                $frontendHeader = $this->getHeader($fieldName, $localeCode, self::FIELD_FALLBACK, $this->delimiter);
                $backendHeaders = $this->getHeader(
                    $fieldName,
                    $localeCode,
                    self::FIELD_FALLBACK,
                    $this->convertDelimiter
                );
                $conversionRules[$frontendHeader] = $backendHeaders;

                $frontendHeader = $this->getHeader($fieldName, $localeCode, self::FIELD_VALUE, $this->delimiter);
                $backendHeaders = $this->getHeader(
                    $fieldName,
                    $localeCode,
                    $targetField,
                    $this->convertDelimiter
                );
                $conversionRules[$frontendHeader] = $backendHeaders;
            }

            return;
        }

        parent::processRelation($conversionRules, $fieldHeader, $field, $entityName);
    }

    /**
     * @param string $fieldName
     * @param string $identity
     * @param string $targetFieldName
     * @param string $delimiter
     * @return string
     */
    protected function getHeader($fieldName, $identity, $targetFieldName, $delimiter)
    {
        return $fieldName . $delimiter . LocaleCodeFormatter::format($identity) . $delimiter . $targetFieldName;
    }
}
