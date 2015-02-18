<?php

namespace OroB2B\Bundle\AttributeBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

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
        $result['order'] = $value['order'];
        $result['master_option_id'] = $value['master_option_id'];
        if (!empty($value['data'])) {
            foreach ($value['data'] as $localeId => $data) {
                if (null == $localeId) {
                    $result['default'] = $data['value'];
                    $result['is_default'] = $data['is_default'];
                } else {
                    if ($this->localized) {
                        $result['locales'][$localeId]['fallback_value'] = $data['value'];
                        $result['locales'][$localeId]['extend_value'] = $data['is_default'];
                    } else {
                        $result['locales'][$localeId] = $data['value'];
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
            'master_option_id' => $value['master_option_id'],
            'order' => $value['order'],
            'data' => [
              null => [
                  'value' => $value['default'],
                  'is_default' => $value['is_default']
              ]
            ]
        ];

        $localeValues = $value['locales'];
        if (!empty($localeValues)) {
            foreach ($localeValues as $localeId => $localeValue) {
                if ($this->localized) {
                    $result['data'][$localeId]['value'] = $localeValue['fallback_value'];
                    $result['data'][$localeId]['is_default'] = $localeValue['extend_value'];
                } else {
                    $result['data'][$localeId] = [
                        'value' => $localeValue,
                        'is_default' => false
                    ];
                }
            }
        }

        return $result;
    }
}
