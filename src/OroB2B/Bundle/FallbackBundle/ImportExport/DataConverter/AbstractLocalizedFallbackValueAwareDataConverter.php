<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

use Oro\Bundle\UIBundle\Tools\ArrayUtils;
use OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocaleCodeFormatter;
use OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository;

abstract class AbstractLocalizedFallbackValueAwareDataConverter extends AbstractTableDataConverter
{
    const FIELD_VALUE = 'value';
    const FIELD_FALLBACK = 'fallback';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var FieldHelper */
    protected $fieldHelper;

    /** @var string */
    protected $localeClassName;

    /** @var string */
    protected $localizedFallbackValueClassName;

    /** @var string[] */
    private $localeCodes;

    /**
     * @param ManagerRegistry $registry
     * @param FieldHelper $fieldHelper
     * @param string $localizedFallbackValueClassName
     * @param string $localeClassName
     */
    public function __construct(
        ManagerRegistry $registry,
        FieldHelper $fieldHelper,
        $localizedFallbackValueClassName,
        $localeClassName
    ) {
        $this->registry = $registry;
        $this->fieldHelper = $fieldHelper;
        $this->localizedFallbackValueClassName = $localizedFallbackValueClassName;
        $this->localeClassName = $localeClassName;
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

    /** {@inheritdoc} */
    protected function getBackendHeader()
    {
        $rules = $this->getHeaderConversionRules();
        $headers = reset($rules);

        return array_keys($headers);
    }

    /** @return string */
    abstract protected function getHolderClassName();

    protected function processRelation(&$rules, &$backendHeaders, $fieldHeader, $field)
    {
        $targetClass = $field['related_entity_name'];

        if (!is_a($targetClass, $this->localizedFallbackValueClassName, true)) {
            return;
        }

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
    }
}
