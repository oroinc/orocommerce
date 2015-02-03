<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class String extends AbstractAttributeType
{
    const NAME = 'string';
    protected $dataTypeField = 'string';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters()
    {
        return [
          'type' => 'text'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isContainHtml()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedForSearch()
    {
        return true;
    }
}
