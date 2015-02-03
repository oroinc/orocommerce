<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class Boolean extends AbstractAttributeType
{
    const NAME = 'boolean';
    protected $dataTypeField = 'integer';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters()
    {
        return [
          'type' => 'checkbox'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInFilters()
    {
        return true;
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
