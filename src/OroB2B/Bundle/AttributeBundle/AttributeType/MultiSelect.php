<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocalizedMultiselectCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\MultiSelectAttributeTypeType;
use OroB2B\Bundle\AttributeBundle\Form\Type\NotLocalizedMultiselectCollectionType;

class MultiSelect extends AbstractAttributeType implements OptionAttributeTypeInterface
{
    const NAME = 'multiselect';
    protected $dataTypeField = 'options';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(Attribute $attribute)
    {
        return [
            'type' => MultiSelectAttributeTypeType::NAME
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValueFormParameters(Attribute $attribute)
    {
        if ($attribute->isLocalized()) {
            return [
                'type' => LocalizedMultiselectCollectionType::NAME,
                'options' => []
            ];
        } else {
            return [
                'type' => NotLocalizedMultiselectCollectionType::NAME,
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
