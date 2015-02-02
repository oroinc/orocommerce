<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class Float extends AbstractAttributeType
{
    const NAME = 'float';
    protected $dataTypeField = 'float';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters()
    {
        return [
          'type'  => 'number'
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
