<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class Integer extends AbstractAttributeType
{
    const NAME = 'integer';
    protected $dataTypeField = 'integer';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters()
    {
        return [
          'type' => 'integer'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInFilters()
    {
        return true;
    }
}
