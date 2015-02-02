<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class Date implements AttributeTypeInterface
{
    const NAME = 'date';
    const DATA_TYPE_FIELD = 'datetime';
    const FORM_TYPE = 'oro_date';

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
        return false;
    }
}
