<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\Type\SelectAttributeTypeType;

class Select extends AbstractAttributeType implements OptionAttributeTypeInterface
{
    const NAME = 'select';
    protected $dataTypeField = 'options';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(Attribute $attribute)
    {
        return [
            'type' => SelectAttributeTypeType::NAME
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
                'type' => 'options_localized',
                'options' => []
            ];
        } else {
            return [
                'type' => 'options_not_localized',
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
