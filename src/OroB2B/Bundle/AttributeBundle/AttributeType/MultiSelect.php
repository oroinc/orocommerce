<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;

class MultiSelect extends AbstractAttributeType implements OptionAttributeTypeInterface
{
    const NAME = 'multiselect';
    protected $dataTypeField = 'options';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(Attribute $attribute)
    {
        // TODO: set correct form parameters during implementation of https://magecore.atlassian.net/browse/BB-303
        return [
            'type' => 'entity',
            ['multiple' => true]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValueFormParameters(Attribute $attribute)
    {
        // TODO: set correct form parameters during implementation of https://magecore.atlassian.net/browse/BB-301
        if ($attribute->isLocalized()) {
            return [
                'type' => 'options_multiple_localized',
                'options' => []
            ];
        } else {
            return [
                'type' => 'options_multiple_not_localized',
                'options' => []
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUnique()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedForSearch()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInFilters()
    {
        return true;
    }
}
