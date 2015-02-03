<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\GreaterThanZero;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Integer as IntegerConstraint;

class Integer extends AbstractAttributeType
{
    const NAME = 'integer';
    protected $dataTypeField = 'integer';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters()
    {
        return [
          'type' => 'integer'
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
            new Decimal()
        ];
    }
}
