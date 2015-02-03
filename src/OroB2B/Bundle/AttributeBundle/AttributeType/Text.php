<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class Text extends AbstractAttributeType
{
    const NAME = 'text';
    protected $dataTypeField = 'text';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(array $options = null)
    {
        return [
          'type' => 'textarea'
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
