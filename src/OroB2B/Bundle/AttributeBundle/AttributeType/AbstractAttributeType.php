<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

abstract class AbstractAttributeType implements AttributeTypeInterface
{
    /**
     * Name of attribute type.
     * This constant must be defined in descendant classes.
     */
    const NAME = '';

    /**
     * Field for data type mapping.
     * This parameter must be defined in descendant classes.
     *
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

    /**
     * {@inheritdoc}
     */
    public function canBeUnique()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeRequired()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value)
    {
        return $value;
    }
}
