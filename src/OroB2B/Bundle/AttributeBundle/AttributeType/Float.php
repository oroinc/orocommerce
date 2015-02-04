<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Integer as IntegerConstraint;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\GreaterThanZero;

class Float extends AbstractAttributeType
{
    const NAME = 'float';
    protected $dataTypeField = 'float';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters()
    {
        return [
          'type' => 'number'
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
            new Decimal()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionalConstraints()
    {
        return [
            new GreaterThanZero(),
            new IntegerConstraint()
        ];
    }
}
