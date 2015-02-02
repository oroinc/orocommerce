<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class Date extends AbstractAttributeType
{
    const NAME = 'date';
    protected $dataTypeField = 'datetime';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters()
    {
        return [
          'type'  => 'oro_date'
        ];
    }
}
