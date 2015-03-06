<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Integer as IntegerConstraint;

class Integer extends AbstractAttributeType
{
    const NAME = 'integer';
    protected $dataTypeField = 'integer';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(Attribute $attribute)
    {
        return [
            'type' => 'integer',
            'options' => ['type' => 'text']
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
        return [
            new IntegerConstraint()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionalConstraints()
    {
        return [
            new GreaterThanZero(),
        ];
    }
}
