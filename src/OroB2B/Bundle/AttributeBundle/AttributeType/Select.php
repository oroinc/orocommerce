<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocalizedSelectCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\NotLocalizedSelectCollectionType;
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
        if ($attribute->isLocalized()) {
            return [
                'type' => LocalizedSelectCollectionType::NAME
            ];
        } else {
            return [
                'type' => NotLocalizedSelectCollectionType::NAME
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
