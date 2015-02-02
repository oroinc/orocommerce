<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class Float implements AttributeTypeInterface
{
    const NAME = 'float';
    const DATA_TYPE_FIELD = 'float';
    const FORM_TYPE = 'number';

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
