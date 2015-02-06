<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;

class DateTime extends AbstractAttributeType
{
    const NAME = 'datetime';
    protected $dataTypeField = 'datetime';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(Attribute $attribute)
    {
        return [
          'type' => 'oro_datetime'
        ];
    }
}
