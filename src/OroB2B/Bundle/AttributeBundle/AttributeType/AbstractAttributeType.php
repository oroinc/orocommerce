<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

abstract class AbstractAttributeType implements AttributeTypeInterface
{
    const NAME = '';
    protected $dataTypeField = '';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataTypeField()
    {
        return $this->dataTypeField;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getFormParameters();

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
