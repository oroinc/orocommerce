<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;

class Boolean extends AbstractAttributeType
{
    const NAME = 'boolean';
    protected $dataTypeField = 'integer';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(Attribute $attribute)
    {
        return [
          'type' => 'checkbox'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInFilters()
    {
        return true;
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
    public function canBeRequired()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value)
    {
        return (bool)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value)
    {
        return !empty($value) ? 1 : 0;
    }
}
