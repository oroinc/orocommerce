<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

abstract class AbstractAttributeType implements AttributeTypeInterface
{
    /**
     * Name of attribute type.
     * These constant must be defined in descendant classes.
     */
    const NAME = '';

    /**
     * Field for data type mapping.
     * These parameter must be defined in descendant classes.
     * @var string
     */
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
