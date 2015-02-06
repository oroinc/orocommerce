<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;

class Date extends AbstractAttributeType
{
    const NAME = 'date';
    protected $dataTypeField = 'datetime';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(Attribute $attribute)
    {
        return [
          'type' => 'oro_date'
        ];
    }
}
