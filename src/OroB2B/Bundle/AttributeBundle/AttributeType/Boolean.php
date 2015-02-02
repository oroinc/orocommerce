<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class Boolean implements AttributeTypeInterface
{
    const NAME = 'boolean';
    const DATA_TYPE_FIELD = 'integer';
    const FORM_TYPE = 'checkbox';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataTypeField()
    {
        return self::DATA_TYPE_FIELD;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(array $options = null)
    {
        return [
          'type'  => self::FORM_TYPE,
          'options'  => $options
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isContainHtml()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedForSearch()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInFilters()
    {
        return true;
    }
}
