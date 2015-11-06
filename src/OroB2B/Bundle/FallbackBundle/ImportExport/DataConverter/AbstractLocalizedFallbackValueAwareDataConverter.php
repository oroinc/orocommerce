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

    protected function processRelation(&$rules, &$backendHeaders, $fieldHeader, $field)
    {
        $targetClass = $field['related_entity_name'];

        if (is_a($targetClass, $this->localizedFallbackValueClassName, true)) {
            if ($this->fieldHelper->isSingleRelation($field)) {
                $rules[$fieldHeader] = ['value' => $fieldHeader, 'order' => false];
                $backendHeaders[] = $rules[$fieldHeader];
            }
            if ($this->fieldHelper->isMultipleRelation($field)) {
                $localeCodes = $this->getLocaleCodes();

                foreach ($localeCodes as $localeCode) {
                    foreach ([self::FIELD_VALUE, self::FIELD_FALLBACK] as $exportField) {
                        $title = implode('.', [$field['name'], LocaleCodeFormatter::format($localeCode), $exportField]);
                        $rules[$title] = ['value' => $title, 'order' => false];
                        $backendHeaders[] = $rules[$title];
                    }
                }
            }

            return;
        }

        parent::processRelation($rules, $backendHeaders, $fieldHeader, $field);
    }
}
