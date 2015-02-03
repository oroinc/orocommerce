<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class DateTime extends AbstractAttributeType
{
    const NAME = 'datetime';
    protected $dataTypeField = 'datetime';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters()
    {
        return [
          'type' => 'oro_datetime'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredConstraints()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionalConstraints()
    {
        return [];
    }
}
