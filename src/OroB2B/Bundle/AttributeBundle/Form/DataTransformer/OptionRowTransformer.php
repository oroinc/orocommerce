<?php

namespace OroB2B\Bundle\AttributeBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\AttributeBundle\Form\Type\HiddenFallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\OptionRowType;

class OptionRowTransformer implements DataTransformerInterface
{
    /** @var boolean */
    protected $localized;

    /**
     * @param boolean $localized
     */
    public function __construct($localized)
    {
        $this->localized = $localized;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return [];
        }

        $result = [];

        $result[OptionRowType::ORDER] = 0;
        if (isset($value[OptionRowType::ORDER])) {
            $result[OptionRowType::ORDER] = $value[OptionRowType::ORDER];
        }

        $result[OptionRowType::MASTER_OPTION_ID] = null;
        if (isset($value[OptionRowType::MASTER_OPTION_ID])) {
            $result[OptionRowType::MASTER_OPTION_ID] = $value[OptionRowType::MASTER_OPTION_ID];
        }

        if (!empty($value['data'])) {
            foreach ($value['data'] as $localeId => $data) {
                if (null == $localeId) {
                    $result[OptionRowType::DEFAULT_VALUE] = $data['value'];
                    $result[OptionRowType::IS_DEFAULT] = $data[OptionRowType::IS_DEFAULT];
                } else {
                    if ($this->localized) {
                        $result[OptionRowType::LOCALES][$localeId][HiddenFallbackValueType::FALLBACK_VALUE]
                            = $data['value'];
                        $result[OptionRowType::LOCALES][$localeId][HiddenFallbackValueType::EXTEND_VALUE]
                            = $data[OptionRowType::IS_DEFAULT];
                    } else {
                        $result[OptionRowType::LOCALES][$localeId] = $data['value'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        $result = [
            OptionRowType::MASTER_OPTION_ID => $value[OptionRowType::MASTER_OPTION_ID],
            OptionRowType::ORDER => $value[OptionRowType::ORDER],
            'data' => [
                null => [
                  'value' => $value[OptionRowType::DEFAULT_VALUE],
                  OptionRowType::IS_DEFAULT => $value[OptionRowType::IS_DEFAULT]
                ]
            ]
        ];

        $localeValues = $value[OptionRowType::LOCALES];
        if (!empty($localeValues)) {
            foreach ($localeValues as $localeId => $localeValue) {
                if ($this->localized) {
                    $result['data'][$localeId]['value'] = $localeValue[HiddenFallbackValueType::FALLBACK_VALUE];
                    $result['data'][$localeId][OptionRowType::IS_DEFAULT]
                        = $localeValue[HiddenFallbackValueType::EXTEND_VALUE];
                } else {
                    $result['data'][$localeId] = [
                        'value' => $localeValue,
                        OptionRowType::IS_DEFAULT => false
                    ];
                }
            }
        }

        return $result;
    }
}
