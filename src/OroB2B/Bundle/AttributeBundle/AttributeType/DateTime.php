<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class DateTime implements AttributeTypeInterface
{
    const NAME = 'datetime';
    const DATA_TYPE_FIELD = 'datetime';
    const FORM_TYPE = 'oro_datetime';

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
